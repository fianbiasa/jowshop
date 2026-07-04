<?php

namespace App\Http\Controllers;

use App\Concerns\ManagesCheckoutSession;
use App\Enums\FunnelEventType;
use App\Enums\OfferStage;
use App\Enums\OfferTriggerCondition;
use App\Enums\OrderItemType;
use App\Models\Funnel;
use App\Models\FunnelOffer;
use App\Models\Order;
use App\Services\FunnelTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CheckoutOfferController extends Controller
{
    use ManagesCheckoutSession;

    /**
     * Show a single order bump offer during checkout.
     */
    public function show(Request $request, Funnel $funnel, FunnelOffer $offer, FunnelTracker $tracker): Response|RedirectResponse
    {
        $this->ensureOfferBelongsToFunnel($funnel, $offer);

        $orderId = $request->session()->get($this->orderSessionKey($funnel));

        if (! is_int($orderId)) {
            return to_route('public.checkout.show', $funnel);
        }

        $session = $tracker->resolveSession($request, $funnel);
        $tracker->recordOnce($session, FunnelEventType::BumpView, $offer);

        $offer->load('product');

        return Inertia::render('public/checkout-offer', [
            'funnel' => ['name' => $funnel->name, 'slug' => $funnel->slug],
            'offer' => [
                'id' => $offer->id,
                'headline' => $offer->headline,
                'description' => $offer->description,
                'product_name' => $offer->product->name,
                'price' => $offer->effectivePrice(),
            ],
        ]);
    }

    /**
     * Record the visitor's accept/decline response to a bump offer, add the
     * order item if accepted, and move to the next step of the bump chain.
     */
    public function respond(Request $request, Funnel $funnel, FunnelOffer $offer, FunnelTracker $tracker): RedirectResponse
    {
        $this->ensureOfferBelongsToFunnel($funnel, $offer);

        $validated = $request->validate([
            'response' => ['required', Rule::in(['accepted', 'declined'])],
        ]);

        $orderId = $request->session()->get($this->orderSessionKey($funnel));

        if (! is_int($orderId)) {
            throw new NotFoundHttpException;
        }

        $order = Order::query()->findOrFail($orderId);
        $session = $tracker->resolveSession($request, $funnel);
        $response = OfferTriggerCondition::from($validated['response']);

        if ($response === OfferTriggerCondition::Accepted) {
            if (! $order->items()->where('funnel_offer_id', $offer->id)->exists()) {
                $order->items()->create([
                    'product_id' => $offer->product_id,
                    'funnel_offer_id' => $offer->id,
                    'offer_type' => OrderItemType::Bump,
                    'quantity' => 1,
                    'unit_price' => $offer->effectivePrice(),
                ]);
                $order->recalculateTotals();
            }

            $tracker->recordOnce($session, FunnelEventType::BumpAccepted, $offer);
        } else {
            $tracker->recordOnce($session, FunnelEventType::BumpDeclined, $offer);
        }

        $next = $offer->nextOfferFor($response);

        if ($next !== null) {
            $request->session()->put($this->offerSessionKey($funnel), $next->id);

            return to_route('public.checkout.offer.show', [$funnel, $next]);
        }

        $request->session()->forget($this->offerSessionKey($funnel));

        return $this->routeAfterBumpChain($funnel, $order);
    }

    private function ensureOfferBelongsToFunnel(Funnel $funnel, FunnelOffer $offer): void
    {
        if ($offer->funnel_id !== $funnel->id || $offer->stage !== OfferStage::Bump) {
            throw new NotFoundHttpException;
        }
    }
}
