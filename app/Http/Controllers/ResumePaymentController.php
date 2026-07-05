<?php

namespace App\Http\Controllers;

use App\Concerns\ManagesCheckoutSession;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResumePaymentController extends Controller
{
    use ManagesCheckoutSession;

    /**
     * One-click link from order/payment emails: verifies the order's
     * payment token, then bootstraps the checkout session so the customer
     * lands straight on the payment-method picker without needing to be on
     * the same browser/session they originally checked out with.
     */
    public function show(Request $request, Order $order, string $token): RedirectResponse
    {
        abort_if($order->payment_token === null || ! hash_equals($order->payment_token, $token), 404);

        if ($order->status !== OrderStatus::Pending) {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => "Pesanan {$order->order_number} sudah berstatus {$order->status->value}.",
            ]);

            return to_route('order-lookup.create');
        }

        $order->loadMissing('funnel');

        $request->session()->put($this->orderSessionKey($order->funnel), $order->id);
        $request->session()->put($this->returnOrderSessionKey($order->funnel), $order->id);

        return to_route('public.checkout.pay', $order->funnel);
    }
}
