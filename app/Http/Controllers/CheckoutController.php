<?php

namespace App\Http\Controllers;

use App\Concerns\ManagesCheckoutSession;
use App\Concerns\SharesMetaPixelProp;
use App\Enums\FunnelEventType;
use App\Enums\FunnelStatus;
use App\Enums\OfferStage;
use App\Enums\OfferTriggerCondition;
use App\Enums\OrderItemType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentGatewayException;
use App\Http\Requests\StoreCheckoutRequest;
use App\Models\Customer;
use App\Models\Funnel;
use App\Models\FunnelOffer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Notifications\OrderConfirmed;
use App\Notifications\PaymentInstructions;
use App\Services\DuitkuGateway;
use App\Services\FunnelTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CheckoutController extends Controller
{
    use ManagesCheckoutSession, SharesMetaPixelProp;

    /**
     * Show the buyer info / address checkout form, or resume an in-progress
     * checkout by redirecting to the current step.
     */
    public function show(Request $request, Funnel $funnel, FunnelTracker $tracker): Response|RedirectResponse
    {
        $this->ensurePublished($funnel);

        $resume = $this->resumeInProgressCheckout($request, $funnel);

        if ($resume !== null) {
            return $resume;
        }

        $session = $tracker->resolveSession($request, $funnel);
        $tracker->recordOnce($session, FunnelEventType::CheckoutView);
        $viewEvent = $session->events()->where('event_type', FunnelEventType::CheckoutView)->first();

        $funnel->load('product');

        return Inertia::render('public/checkout', [
            'metaPixel' => $this->metaPixelProp($this->effectivePixelId($funnel), $viewEvent),
            'funnel' => ['name' => $funnel->name, 'slug' => $funnel->slug],
            'product' => [
                'name' => $funnel->product->name,
                'price' => $funnel->product->price,
                'type' => $funnel->product->type,
            ],
        ]);
    }

    /**
     * Create the order from the buyer's info, then move on to the first
     * order bump (if any) or straight to the payment step.
     */
    public function store(StoreCheckoutRequest $request, Funnel $funnel, FunnelTracker $tracker): RedirectResponse
    {
        $this->ensurePublished($funnel);

        $funnel->load('product');
        $product = $funnel->product;

        if ($product->isPhysical() && $product->stock !== null && $product->stock < 1) {
            throw ValidationException::withMessages(['stock' => 'Stok produk sedang habis.']);
        }

        $validated = $request->validated();
        $session = $tracker->resolveSession($request, $funnel);

        $customer = Customer::query()->updateOrCreate(
            ['email' => $validated['email']],
            ['name' => $validated['name'], 'phone' => $validated['phone']],
        );

        if ($session->visitor->customer_id === null) {
            $session->visitor->update(['customer_id' => $customer->id]);
        }

        $address = null;

        if ($product->isPhysical()) {
            $address = $customer->addresses()->create([
                'recipient_name' => $validated['name'],
                'phone' => $validated['phone'],
                'province' => $validated['province'],
                'city' => $validated['city'],
                'district' => $validated['district'],
                'postal_code' => $validated['postal_code'],
                'destination_area_id' => $validated['destination_area_id'],
                'destination_label' => $validated['destination_label'],
                'address_line' => $validated['address_line'],
            ]);
        }

        $order = Order::query()->create([
            'funnel_id' => $funnel->id,
            'customer_id' => $customer->id,
            'address_id' => $address?->id,
            'visitor_id' => $session->visitor_id,
            'order_number' => Order::generateOrderNumber(),
            'payment_token' => Str::random(40),
            'subtotal' => $product->price,
            'total' => $product->price,
            'status' => OrderStatus::Pending,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'offer_type' => OrderItemType::Main,
            'quantity' => 1,
            'unit_price' => $product->price,
        ]);

        $customer->notify(new OrderConfirmed($order));

        $tracker->recordOnce($session, FunnelEventType::CheckoutSubmitted);

        $request->session()->put($this->orderSessionKey($funnel), $order->id);

        $rootBump = $funnel->rootOfferForStage(OfferStage::Bump);

        if ($rootBump !== null) {
            $request->session()->put($this->offerSessionKey($funnel), $rootBump->id);

            return to_route('public.checkout.offer.show', [$funnel, $rootBump]);
        }

        return $this->routeAfterBumpChain($funnel, $order);
    }

    /**
     * Show the available Duitku payment methods for the in-progress order.
     */
    public function pay(Request $request, Funnel $funnel, DuitkuGateway $gateway): Response
    {
        $this->ensurePublished($funnel);

        $order = $this->orderFromSession($request, $funnel);

        $settings = PaymentSetting::query()->where('is_active', true)->first();

        abort_if($settings === null, 503, 'Pembayaran belum dikonfigurasi.');

        try {
            $methods = $gateway->getPaymentMethods($settings, $gateway->amountAsInt($order->total));
        } catch (PaymentGatewayException $exception) {
            report($exception);

            abort(503, 'Metode pembayaran sedang tidak tersedia, silakan coba lagi beberapa saat lagi.');
        }

        return Inertia::render('public/checkout-payment', [
            'funnel' => ['name' => $funnel->name, 'slug' => $funnel->slug],
            'methods' => $methods,
        ]);
    }

    /**
     * Create the Duitku transaction for the in-progress order using the
     * chosen payment method, then redirect the browser to Duitku's hosted
     * payment page.
     */
    public function payStore(Request $request, Funnel $funnel, FunnelTracker $tracker, DuitkuGateway $gateway): SymfonyResponse
    {
        $this->ensurePublished($funnel);

        $validated = $request->validate([
            'payment_method' => ['required', 'string'],
        ]);

        $order = $this->orderFromSession($request, $funnel);

        $settings = PaymentSetting::query()->where('is_active', true)->first();

        abort_if($settings === null, 503, 'Pembayaran belum dikonfigurasi.');

        $session = $tracker->resolveSession($request, $funnel);
        $session->update(['order_id' => $order->id]);

        Payment::query()->firstOrCreate(
            ['order_id' => $order->id],
            [
                'merchant_order_id' => $order->order_number,
                'amount' => $order->total,
                'payment_method' => $validated['payment_method'],
                'status' => PaymentStatus::Pending,
            ],
        );

        $tracker->recordOnce($session, FunnelEventType::PaymentPending);

        $request->session()->forget($this->offerSessionKey($funnel));

        try {
            $transaction = $gateway->createTransaction(
                $settings,
                $order,
                $gateway->amountAsInt($order->total),
                $order->order_number,
                $validated['payment_method'],
                route('webhooks.duitku'),
                route('public.checkout.return', $funnel),
            );
        } catch (PaymentGatewayException $exception) {
            report($exception);

            abort(503, 'Pembayaran sedang tidak tersedia, silakan coba lagi beberapa saat lagi.');
        }

        $order->loadMissing('customer');
        $order->customer->notify(new PaymentInstructions($order, (string) $transaction['paymentUrl']));

        return Inertia::location((string) $transaction['paymentUrl']);
    }

    /**
     * The page the customer lands on after returning from Duitku's payment
     * page (initial payment, or any later incremental upsell payment).
     *
     * If the order is paid and the post-purchase upsell/downsell chain
     * hasn't been resolved yet, this continues that chain (starting it, or
     * moving to the next offer after an accepted one). Otherwise it shows
     * the final order status — informational only, the webhook callback is
     * the authoritative source of truth for payment status.
     */
    public function return(Request $request, Funnel $funnel, FunnelTracker $tracker): Response|RedirectResponse
    {
        $this->ensurePublished($funnel);

        $orderId = $request->session()->get($this->orderSessionKey($funnel));

        if (! is_int($orderId)) {
            throw new NotFoundHttpException;
        }

        $order = Order::query()->with(['items.product', 'customer'])->findOrFail($orderId);

        $session = $tracker->resolveSession($request, $funnel);

        $next = $this->nextPostPurchaseOffer($request, $funnel, $order);

        if ($next !== null) {
            $request->session()->put($this->offerSessionKey($funnel), $next->id);

            return to_route('public.checkout.upsell.show', [$funnel, $next]);
        }

        $request->session()->put($this->postPurchaseDoneKey($funnel), true);
        $tracker->recordOnce($session, FunnelEventType::ThankyouView);

        $paymentSuccessEvent = $order->status === OrderStatus::Paid
            ? $session->events()->where('event_type', FunnelEventType::PaymentSuccess)->first()
            : null;

        $request->session()->forget([
            $this->orderSessionKey($funnel),
            $this->offerSessionKey($funnel),
        ]);

        // The buyer's session already owns this order (it was just checked
        // out in it), so granting order-lookup access here is the same
        // trust level already established, not a new escalation — it lets
        // the thank-you page link straight into "Cek Status Pesanan"
        // without asking the buyer to re-enter their email/order number.
        $request->session()->put("order_lookup.{$order->id}", true);

        return Inertia::render('public/checkout-return', [
            'metaPixel' => $this->metaPixelProp($this->effectivePixelId($funnel), $paymentSuccessEvent, $order),
            'funnel' => ['name' => $funnel->name],
            'order' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'total' => $order->total,
                'items' => $order->items->map(fn ($item) => [
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'is_digital' => $item->product->isDigital(),
                ]),
            ],
            'thankYouMessage' => $funnel->thank_you_content['message'] ?? null,
            'customerEmail' => $order->customer->email,
            'orderLookupUrl' => route('order-lookup.show', $order->order_number),
        ]);
    }

    private function ensurePublished(Funnel $funnel): void
    {
        if ($funnel->status !== FunnelStatus::Published) {
            throw new NotFoundHttpException;
        }
    }

    private function orderFromSession(Request $request, Funnel $funnel): Order
    {
        $orderId = $request->session()->get($this->orderSessionKey($funnel));

        if (! is_int($orderId)) {
            throw new NotFoundHttpException;
        }

        return Order::query()->findOrFail($orderId);
    }

    /**
     * If a checkout is already in progress for this funnel (order exists in
     * session), redirect to whichever step should be shown next instead of
     * re-showing the buyer info form.
     */
    private function resumeInProgressCheckout(Request $request, Funnel $funnel): ?RedirectResponse
    {
        $orderId = $request->session()->get($this->orderSessionKey($funnel));

        if (! is_int($orderId)) {
            return null;
        }

        $offerId = $request->session()->get($this->offerSessionKey($funnel));

        if (is_int($offerId)) {
            return to_route('public.checkout.offer.show', [$funnel, $offerId]);
        }

        return to_route('public.checkout.pay', $funnel);
    }

    /**
     * Determine the next post-purchase upsell/downsell offer to show, if
     * any. Returns null once the chain is exhausted or hasn't started
     * because the order isn't paid yet.
     */
    private function nextPostPurchaseOffer(Request $request, Funnel $funnel, Order $order): ?FunnelOffer
    {
        if ($order->status !== OrderStatus::Paid) {
            return null;
        }

        if ($request->session()->get($this->postPurchaseDoneKey($funnel)) === true) {
            return null;
        }

        $pendingOfferId = $request->session()->get($this->offerSessionKey($funnel));

        if (is_int($pendingOfferId)) {
            $offer = FunnelOffer::query()->find($pendingOfferId);

            return $offer?->nextOfferFor(OfferTriggerCondition::Accepted);
        }

        return $funnel->rootOfferForStage(OfferStage::Upsell);
    }
}
