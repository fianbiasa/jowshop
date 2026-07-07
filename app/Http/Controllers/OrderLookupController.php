<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\SalespageStyle;
use App\Http\Requests\LookupOrderRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderLookupController extends Controller
{
    /**
     * Show the "find my order" form.
     */
    public function create(): Response
    {
        return Inertia::render('public/order-lookup-form');
    }

    /**
     * Verify the email + order number combination and, if it matches,
     * mark this browser session as allowed to view that specific order.
     */
    public function store(LookupOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $order = Order::query()
            ->where('order_number', $validated['order_number'])
            ->whereHas('customer', fn ($query) => $query->where('email', $validated['email']))
            ->first();

        if ($order === null) {
            throw ValidationException::withMessages([
                'order_number' => 'Kombinasi email dan nomor pesanan tidak ditemukan.',
            ]);
        }

        session()->put("order_lookup.{$order->id}", true);

        return to_route('order-lookup.show', $order->order_number);
    }

    /**
     * Show the order status/delivery page, provided this browser session
     * has already verified the email + order number combination.
     */
    public function show(Order $order): Response|RedirectResponse
    {
        if (session()->get("order_lookup.{$order->id}") !== true) {
            return to_route('order-lookup.create');
        }

        $order->load(['items.product', 'items.delivery', 'shipment', 'funnel.salespage']);

        return Inertia::render('public/order-lookup-result', [
            'style' => $order->funnel->salespage?->style ?? SalespageStyle::Minimal,
            'order' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'total' => $order->total,
                'payment_resume_url' => $order->status === OrderStatus::Pending
                    ? $order->resumePaymentUrl()
                    : null,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'is_digital' => $item->product->isDigital(),
                    'download_token' => $item->delivery?->download_token,
                ]),
                'shipment' => $order->shipment !== null ? [
                    'courier' => $order->shipment->courier,
                    'service' => $order->shipment->service,
                    'status' => $order->shipment->status,
                    'tracking_number' => $order->shipment->tracking_number,
                ] : null,
            ],
        ]);
    }
}
