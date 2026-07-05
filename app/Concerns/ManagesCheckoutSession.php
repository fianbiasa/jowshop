<?php

namespace App\Concerns;

use App\Models\Funnel;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;

trait ManagesCheckoutSession
{
    private function orderSessionKey(Funnel $funnel): string
    {
        return "checkout.{$funnel->id}.order_id";
    }

    /**
     * A separate, longer-lived pointer to "the order this session most
     * recently checked out" — unlike orderSessionKey(), this is never
     * forgotten once the checkout+post-purchase-offer flow completes, so
     * CheckoutController::return() can still be refreshed/revisited after
     * that point instead of 404ing (orderSessionKey() itself IS forgotten
     * then, so a later fresh checkout on the same funnel doesn't wrongly
     * resume into this already-completed order).
     */
    private function returnOrderSessionKey(Funnel $funnel): string
    {
        return "checkout.{$funnel->id}.return_order_id";
    }

    private function offerSessionKey(Funnel $funnel): string
    {
        return "checkout.{$funnel->id}.current_offer_id";
    }

    /**
     * Marks that the post-purchase upsell/downsell chain has been fully
     * resolved for the current checkout, so the return page doesn't try to
     * restart it after a decline-only chain (which never touches Duitku).
     */
    private function postPurchaseDoneKey(Funnel $funnel): string
    {
        return "checkout.{$funnel->id}.post_purchase_done";
    }

    /**
     * Once the order bump chain is fully resolved, route to shipping method
     * selection first (if the order has any physical items and no shipment
     * has been chosen yet), otherwise straight to payment.
     */
    private function routeAfterBumpChain(Funnel $funnel, Order $order): RedirectResponse
    {
        if ($order->totalPhysicalWeightGrams() > 0 && $order->shipment === null) {
            return to_route('public.checkout.shipping.show', $funnel);
        }

        return to_route('public.checkout.pay', $funnel);
    }
}
