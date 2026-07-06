<?php

namespace App\Services;

use App\Enums\ShippingProvider;
use App\Exceptions\ShippingRateException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\ShippingSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ShippingRateClient
{
    /**
     * Search the provider's destination area database (used to let the
     * customer pick their shipping destination during checkout).
     *
     * @return array<int, array{id: string, label: string}>
     *
     * @throws ShippingRateException
     */
    public function searchDestination(ShippingSetting $settings, string $query): array
    {
        return match ($settings->provider) {
            ShippingProvider::Komerce, ShippingProvider::RajaOngkir => $this->searchDestinationWithKomerce($settings, $query),
            ShippingProvider::Biteship => $this->searchDestinationWithBiteship($settings, $query),
        };
    }

    /**
     * Calculate shipping cost options for the given destination and order.
     *
     * @return array<int, array{courier: string, service: string, description: string, cost: int, etd: string}>
     *
     * @throws ShippingRateException
     */
    public function calculateRates(ShippingSetting $settings, Order $order, string $destinationAreaId): array
    {
        return $this->calculateRatesForShipment(
            $settings,
            max(1, $order->totalPhysicalWeightGrams()),
            $this->buildBiteshipItems($order),
            $destinationAreaId,
        );
    }

    /**
     * Estimate shipping cost for a single product outside of a real order —
     * used by the public "Cek Ongkir" tool where there's no checkout in
     * progress yet, just "what would this cost".
     *
     * @return array<int, array{courier: string, service: string, description: string, cost: int, etd: string}>
     *
     * @throws ShippingRateException
     */
    public function calculateRatesForProduct(ShippingSetting $settings, Product $product, string $destinationAreaId): array
    {
        $weightGrams = max(1, (int) $product->weight_grams);

        return $this->calculateRatesForShipment($settings, $weightGrams, [[
            'name' => $product->name,
            'value' => (int) round((float) $product->price),
            'weight' => $weightGrams,
            'length' => (int) ($product->length_cm ?? 10),
            'width' => (int) ($product->width_cm ?? 10),
            'height' => (int) ($product->height_cm ?? 10),
            'quantity' => 1,
        ]], $destinationAreaId);
    }

    /**
     * Book an actual courier pickup for the order's chosen shipment,
     * returning the resulting tracking number.
     *
     * @return array{waybill_id: string, tracking_id: string, status: string}
     *
     * @throws ShippingRateException
     */
    public function createOrder(ShippingSetting $settings, Order $order, Shipment $shipment): array
    {
        return match ($settings->provider) {
            ShippingProvider::Biteship => $this->createOrderWithBiteship($settings, $order, $shipment),
            ShippingProvider::Komerce, ShippingProvider::RajaOngkir => throw new \RuntimeException('Automatic shipment booking is not supported for this provider yet.'),
        };
    }

    /**
     * Look up the live delivery status of a manually-entered (or
     * auto-booked) tracking number against the courier's own system.
     *
     * @return array{status: string, history: array<int, array{status: string, note: string, updated_at: string}>}
     *
     * @throws ShippingRateException
     */
    public function trackShipment(ShippingSetting $settings, string $courier, string $trackingNumber): array
    {
        return match ($settings->provider) {
            ShippingProvider::Biteship => $this->trackShipmentWithBiteship($settings, $courier, $trackingNumber),
            ShippingProvider::Komerce, ShippingProvider::RajaOngkir => throw new \RuntimeException('Tracking lookup is not supported for this provider yet.'),
        };
    }

    /**
     * @return array<int, array{id: string, label: string}>
     *
     * @throws ShippingRateException
     */
    private function searchDestinationWithKomerce(ShippingSetting $settings, string $query): array
    {
        try {
            $response = Http::withHeaders(['key' => $settings->api_key])
                ->timeout(15)
                ->get("{$settings->baseApiUrl()}/destination/domestic-destination", [
                    'search' => $query,
                    'limit' => 10,
                ]);
        } catch (ConnectionException $exception) {
            throw ShippingRateException::fromConnectionFailure($exception);
        }

        if ($response->failed()) {
            throw ShippingRateException::fromResponse($response->status(), $response->body());
        }

        $rows = $response->json('data', []);

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_map(
            fn (array $row): array => [
                'id' => (string) ($row['id'] ?? ''),
                'label' => (string) ($row['label'] ?? ''),
            ],
            $rows,
        ));
    }

    /**
     * @return array<int, array{courier: string, service: string, description: string, cost: int, etd: string}>
     *
     * @throws ShippingRateException
     */
    private function calculateRatesForShipment(ShippingSetting $settings, int $weightGrams, array $items, string $destinationAreaId): array
    {
        return match ($settings->provider) {
            ShippingProvider::Komerce, ShippingProvider::RajaOngkir => $this->calculateRatesWithKomerce($settings, $weightGrams, $destinationAreaId),
            ShippingProvider::Biteship => $this->calculateRatesWithBiteship($settings, $items, $destinationAreaId),
        };
    }

    /**
     * @return array<int, array{courier: string, service: string, description: string, cost: int, etd: string}>
     *
     * @throws ShippingRateException
     */
    private function calculateRatesWithKomerce(ShippingSetting $settings, int $weightGrams, string $destinationAreaId): array
    {
        $couriers = $settings->enabled_couriers ?? ['jne', 'jnt', 'sicepat'];

        try {
            $response = Http::asForm()->withHeaders(['key' => $settings->api_key])
                ->timeout(15)
                ->post("{$settings->baseApiUrl()}/calculate/domestic-cost", [
                    'origin' => $settings->origin_area_id,
                    'destination' => $destinationAreaId,
                    'weight' => $weightGrams,
                    'courier' => implode(':', $couriers),
                    'price' => 'lowest',
                ]);
        } catch (ConnectionException $exception) {
            throw ShippingRateException::fromConnectionFailure($exception);
        }

        if ($response->failed()) {
            throw ShippingRateException::fromResponse($response->status(), $response->body());
        }

        $rows = $response->json('data', []);

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_map(
            fn (array $row): array => [
                'courier' => (string) ($row['code'] ?? ''),
                'service' => (string) ($row['service'] ?? ''),
                'description' => (string) ($row['description'] ?? ($row['name'] ?? '')),
                'cost' => (int) ($row['cost'] ?? 0),
                'etd' => (string) ($row['etd'] ?? ''),
            ],
            $rows,
        ));
    }

    /**
     * @return array<int, array{id: string, label: string}>
     *
     * @throws ShippingRateException
     */
    private function searchDestinationWithBiteship(ShippingSetting $settings, string $query): array
    {
        try {
            $response = Http::withHeaders(['Authorization' => $settings->api_key])
                ->timeout(15)
                ->get('https://api.biteship.com/v1/maps/areas', [
                    'countries' => 'ID',
                    'input' => $query,
                    'type' => 'single',
                ]);
        } catch (ConnectionException $exception) {
            throw ShippingRateException::fromConnectionFailure($exception);
        }

        if ($response->failed()) {
            throw ShippingRateException::fromResponse($response->status(), $response->body());
        }

        $rows = $response->json('areas', []);

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_map(
            fn (array $row): array => [
                'id' => (string) ($row['id'] ?? ''),
                'label' => (string) ($row['name'] ?? ''),
            ],
            $rows,
        ));
    }

    /**
     * @return array<int, array{courier: string, service: string, description: string, cost: int, etd: string}>
     *
     * @throws ShippingRateException
     */
    private function calculateRatesWithBiteship(ShippingSetting $settings, array $items, string $destinationAreaId): array
    {
        $couriers = $settings->enabled_couriers ?? ['jne', 'jnt', 'sicepat'];

        try {
            $response = Http::withHeaders(['Authorization' => $settings->api_key])
                ->timeout(15)
                ->post('https://api.biteship.com/v1/rates/couriers', [
                    'origin_area_id' => $settings->origin_area_id,
                    'destination_area_id' => $destinationAreaId,
                    'couriers' => implode(',', $couriers),
                    'items' => $items,
                ]);
        } catch (ConnectionException $exception) {
            throw ShippingRateException::fromConnectionFailure($exception);
        }

        if ($response->failed()) {
            throw ShippingRateException::fromResponse($response->status(), $response->body());
        }

        $rows = $response->json('pricing', []);

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_map(
            fn (array $row): array => [
                'courier' => (string) ($row['courier_code'] ?? ''),
                'service' => (string) ($row['courier_service_code'] ?? ''),
                'description' => (string) ($row['description'] ?? ($row['courier_service_name'] ?? '')),
                'cost' => (int) ($row['price'] ?? 0),
                'etd' => (string) ($row['duration'] ?? ''),
            ],
            $rows,
        ));
    }

    /**
     * Book a real courier pickup for the order's chosen shipment. Unlike
     * rate calculation, this spends real Biteship balance and dispatches an
     * actual courier — callers must gate this behind an explicit opt-in.
     *
     * @return array{waybill_id: string, tracking_id: string, status: string}
     *
     * @throws ShippingRateException
     */
    private function createOrderWithBiteship(ShippingSetting $settings, Order $order, Shipment $shipment): array
    {
        $order->loadMissing('address');
        $address = $order->address;

        try {
            $response = Http::withHeaders(['Authorization' => $settings->api_key])
                ->timeout(15)
                ->post('https://api.biteship.com/v1/orders', [
                    'shipper_contact_name' => $settings->origin_contact_name,
                    'shipper_contact_phone' => $settings->origin_contact_phone,
                    'origin_contact_name' => $settings->origin_contact_name,
                    'origin_contact_phone' => $settings->origin_contact_phone,
                    'origin_address' => $settings->origin_address,
                    'origin_postal_code' => (int) $settings->origin_postal_code,
                    'origin_area_id' => $settings->origin_area_id,
                    'destination_contact_name' => $address->recipient_name,
                    'destination_contact_phone' => $address->phone,
                    'destination_address' => $address->address_line,
                    'destination_postal_code' => (int) $address->postal_code,
                    'destination_area_id' => $address->destination_area_id,
                    'courier_company' => $shipment->courier,
                    'courier_type' => $shipment->service,
                    'delivery_type' => 'now',
                    'reference_id' => $order->order_number,
                    'items' => $this->buildBiteshipItems($order),
                ]);
        } catch (ConnectionException $exception) {
            throw ShippingRateException::fromConnectionFailure($exception);
        }

        if ($response->failed()) {
            throw ShippingRateException::fromResponse($response->status(), $response->body());
        }

        return [
            'waybill_id' => (string) $response->json('courier.waybill_id', ''),
            'tracking_id' => (string) $response->json('courier.tracking_id', ''),
            'status' => (string) $response->json('status', ''),
        ];
    }

    /**
     * @return array{status: string, history: array<int, array{status: string, note: string, updated_at: string}>}
     *
     * @throws ShippingRateException
     */
    private function trackShipmentWithBiteship(ShippingSetting $settings, string $courier, string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders(['Authorization' => $settings->api_key])
                ->timeout(15)
                ->get("https://api.biteship.com/v1/trackings/{$trackingNumber}/couriers/{$courier}");
        } catch (ConnectionException $exception) {
            throw ShippingRateException::fromConnectionFailure($exception);
        }

        if ($response->failed()) {
            throw ShippingRateException::fromResponse($response->status(), $response->body());
        }

        $history = $response->json('history', []);

        if (! is_array($history)) {
            $history = [];
        }

        return [
            'status' => (string) $response->json('status', ''),
            'history' => array_values(array_map(
                fn (array $row): array => [
                    'status' => (string) ($row['status'] ?? ''),
                    'note' => (string) ($row['note'] ?? ''),
                    'updated_at' => (string) ($row['updated_at'] ?? ''),
                ],
                $history,
            )),
        ];
    }

    /**
     * @return array<int, array{name: string, value: int, weight: int, length: int, width: int, height: int, quantity: int}>
     */
    private function buildBiteshipItems(Order $order): array
    {
        $order->loadMissing('items.product');

        return $order->items
            ->filter(fn (OrderItem $item) => $item->product->isPhysical())
            ->map(fn (OrderItem $item): array => [
                'name' => $item->product->name,
                'value' => (int) round((float) $item->unit_price),
                'weight' => (int) $item->product->weight_grams,
                'length' => (int) ($item->product->length_cm ?? 10),
                'width' => (int) ($item->product->width_cm ?? 10),
                'height' => (int) ($item->product->height_cm ?? 10),
                'quantity' => $item->quantity,
            ])
            ->values()
            ->all();
    }
}
