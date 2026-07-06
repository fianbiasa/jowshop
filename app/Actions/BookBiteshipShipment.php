<?php

namespace App\Actions;

use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Exceptions\ShippingRateException;
use App\Models\Order;
use App\Models\ShippingSetting;
use App\Services\ShippingRateClient;

class BookBiteshipShipment
{
    public function __construct(private readonly ShippingRateClient $client) {}

    /**
     * Books a real Biteship courier pickup for a just-paid order's physical
     * shipment, storing the resulting waybill as its tracking number. Only
     * acts when auto-booking is explicitly enabled — this spends real
     * balance and dispatches an actual courier, unlike rate calculation. A
     * booking failure is logged but never blocks the payment webhook: the
     * order stays paid, and the shipment can be booked manually afterward.
     */
    public function __invoke(Order $order): void
    {
        $shipment = $order->shipment;

        if ($shipment === null || $shipment->tracking_number !== null) {
            return;
        }

        $settings = ShippingSetting::query()->where('is_active', true)->first();

        if ($settings === null || $settings->provider !== ShippingProvider::Biteship || ! $settings->auto_book_shipping) {
            return;
        }

        try {
            $result = $this->client->createOrder($settings, $order, $shipment);
        } catch (ShippingRateException $exception) {
            report($exception);

            return;
        }

        $shipment->update([
            'tracking_number' => $result['waybill_id'],
            'status' => ShipmentStatus::Processing,
        ]);
    }
}
