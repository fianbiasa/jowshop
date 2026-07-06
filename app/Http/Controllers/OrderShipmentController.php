<?php

namespace App\Http\Controllers;

use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Exceptions\ShippingRateException;
use App\Http\Requests\UpdateShipmentRequest;
use App\Models\Order;
use App\Models\ShippingSetting;
use App\Notifications\ShipmentTrackingAvailable;
use App\Services\ShippingRateClient;
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

    /**
     * Check a shipment's live delivery status against the courier's own
     * system — works for manually-entered resi numbers too, not just ones
     * booked through this app.
     */
    public function track(Order $order, ShippingRateClient $client): RedirectResponse
    {
        $shipment = $order->shipment;

        abort_if($shipment === null, 404);

        if (blank($shipment->tracking_number)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Isi nomor resi terlebih dahulu.')]);

            return to_route('orders.show', $order);
        }

        $settings = ShippingSetting::query()->where('is_active', true)->first();

        if ($settings === null || $settings->provider !== ShippingProvider::Biteship) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Cek tracking otomatis hanya didukung untuk provider Biteship saat ini.')]);

            return to_route('orders.show', $order);
        }

        try {
            $result = $client->trackShipment($settings, $shipment->courier, $shipment->tracking_number);
        } catch (ShippingRateException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Gagal mengambil tracking: :message', ['message' => $exception->getMessage()])]);

            return to_route('orders.show', $order);
        }

        Inertia::flash('shipment_tracking', $result);

        return to_route('orders.show', $order);
    }
}
