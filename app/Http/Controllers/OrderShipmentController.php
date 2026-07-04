<?php

namespace App\Http\Controllers;

use App\Enums\ShipmentStatus;
use App\Http\Requests\UpdateShipmentRequest;
use App\Models\Order;
use App\Notifications\ShipmentTrackingAvailable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class OrderShipmentController extends Controller
{
    /**
     * Update the tracking number/status of an order's shipment. Notifies
     * the customer by email the first time a tracking number is set.
     */
    public function update(UpdateShipmentRequest $request, Order $order): RedirectResponse
    {
        $shipment = $order->shipment;

        abort_if($shipment === null, 404);

        $validated = $request->validated();
        $hadNoTrackingNumberBefore = blank($shipment->tracking_number);

        $shipment->update([
            'tracking_number' => $validated['tracking_number'] ?? null,
            'status' => $validated['status'],
            'shipped_at' => $validated['status'] === ShipmentStatus::Shipped->value && $shipment->shipped_at === null
                ? now()
                : $shipment->shipped_at,
            'delivered_at' => $validated['status'] === ShipmentStatus::Delivered->value && $shipment->delivered_at === null
                ? now()
                : $shipment->delivered_at,
        ]);

        if ($hadNoTrackingNumberBefore && filled($shipment->tracking_number)) {
            $order->loadMissing('customer');
            $order->customer->notify(new ShipmentTrackingAvailable($shipment));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shipment updated.')]);

        return to_route('orders.show', $order);
    }
}
