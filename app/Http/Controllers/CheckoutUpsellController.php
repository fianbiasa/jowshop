<?php

namespace App\Http\Controllers;

use App\Concerns\ManagesCheckoutSession;
use App\Enums\FunnelEventType;
use App\Enums\OfferStage;
use App\Enums\OfferTriggerCondition;
use App\Enums\OrderItemType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentGatewayException;
use App\Models\Funnel;
use App\Models\FunnelOffer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Services\DuitkuGateway;
use App\Services\FunnelTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CheckoutUpsellController extends Controller
{
    use ManagesCheckoutSession;

    /**
     * Show a single post-purchase upsell/downsell offer.
     */
    public function show(Request $request, Funnel $funnel, FunnelOffer $offer, FunnelTracker $tracker): Response
    {
        $this->ensureOfferBelongsToFunnel($funnel, $offer);

        $order = $this->paidOrderFromSession($request, $funnel);

        $session = $tracker->resolveSession($request, $funnel);
        $tracker->recordOnce(
            $session,
            $offer->stage === OfferStage::Upsell ? FunnelEventType::UpsellView : FunnelEventType::DownsellView,
            $offer,
        );

        $offer->load('product');

        return Inertia::render('public/checkout-upsell', [
            'funnel' => ['name' => $funnel->name, 'slug' => $funnel->slug],
            'order' => ['order_number' => $order->order_number],
            'offer' => [
                'id' => $offer->id,
                'stage' => $offer->stage,
                'headline' => $offer->headline,
                'description' => $offer->description,
                'product_name' => $offer->product->name,
                'price' => $offer->effectivePrice(),
            ],
        ]);
    }

    /**
     * Record the visitor's accept/decline response. Declining moves straight
     * to the next offer (no payment involved). Accepting adds the order
     * item and creates a new incremental Duitku payment for just that
     * offer's price, redirecting to Duitku to collect it (see PRD §10 —
     * without a saved/tokenized card, true one-click charging on the same
     * payment isn't possible for most Duitku methods).
     */
    public function respond(
        Request $request,
        Funnel $funnel,
        FunnelOffer $offer,
        FunnelTracker $tracker,
        DuitkuGateway $gateway,
    ): RedirectResponse {
        $this->ensureOfferBelongsToFunnel($funnel, $offer);

        $validated = $request->validate([
            'response' => ['required', Rule::in(['accepted', 'declined'])],
        ]);

        $order = $this->paidOrderFromSession($request, $funnel);
        $session = $tracker->resolveSession($request, $funnel);
        $response = OfferTriggerCondition::from($validated['response']);
        $isUpsell = $offer->stage === OfferStage::Upsell;

        if ($response === OfferTriggerCondition::Declined) {
            $tracker->recordOnce(
                $session,
                $isUpsell ? FunnelEventType::UpsellDeclined : FunnelEventType::DownsellDeclined,
                $offer,
            );

            $next = $offer->nextOfferFor(OfferTriggerCondition::Declined);

            if ($next !== null) {
                $request->session()->put($this->offerSessionKey($funnel), $next->id);

                return to_route('public.checkout.upsell.show', [$funnel, $next]);
            }

            $request->session()->put($this->postPurchaseDoneKey($funnel), true);

            return to_route('public.checkout.return', $funnel);
        }

        if (! $order->items()->where('funnel_offer_id', $offer->id)->exists()) {
            $order->items()->create([
                'product_id' => $offer->product_id,
                'funnel_offer_id' => $offer->id,
                'offer_type' => $isUpsell ? OrderItemType::Upsell : OrderItemType::Downsell,
                'quantity' => 1,
                'unit_price' => $offer->effectivePrice(),
            ]);
            $order->recalculateTotals();
        }

        $tracker->recordOnce(
            $session,
            $isUpsell ? FunnelEventType::UpsellAccepted : FunnelEventType::DownsellAccepted,
            $offer,
        );

        $settings = PaymentSetting::query()->where('is_active', true)->first();

        abort_if($settings === null, 503, 'Pembayaran belum dikonfigurasi.');

        $merchantOrderId = "{$order->order_number}-O{$offer->id}";

        Payment::query()->firstOrCreate(
            ['merchant_order_id' => $merchantOrderId],
            [
                'order_id' => $order->id,
                'amount' => $offer->effectivePrice(),
                'status' => PaymentStatus::Pending,
            ],
        );

        $request->session()->put($this->offerSessionKey($funnel), $offer->id);

        try {
            $transaction = $gateway->createTransaction(
                $settings,
                $order,
                $gateway->amountAsInt((string) $offer->effectivePrice()),
                $merchantOrderId,
                route('webhooks.duitku'),
                route('public.checkout.return', $funnel),
            );
        } catch (PaymentGatewayException $exception) {
            report($exception);

            abort(503, 'Pembayaran sedang tidak tersedia, silakan coba lagi beberapa saat lagi.');
        }

        return redirect()->away((string) $transaction['paymentUrl']);
    }

    private function ensureOfferBelongsToFunnel(Funnel $funnel, FunnelOffer $offer): void
    {
        if ($offer->funnel_id !== $funnel->id || ! in_array($offer->stage, [OfferStage::Upsell, OfferStage::Downsell], true)) {
            throw new NotFoundHttpException;
        }
    }

    private function paidOrderFromSession(Request $request, Funnel $funnel): Order
    {
        $orderId = $request->session()->get($this->orderSessionKey($funnel));

        if (! is_int($orderId)) {
            throw new NotFoundHttpException;
        }

        $order = Order::query()->findOrFail($orderId);

        abort_unless($order->status === OrderStatus::Paid, 404);

        return $order;
    }
}
