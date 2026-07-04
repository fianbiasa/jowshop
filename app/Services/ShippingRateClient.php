<?php

namespace App\Services;

use App\Exceptions\ShippingRateException;
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
     * Calculate shipping cost options for the given destination and total
     * package weight (grams).
     *
     * @return array<int, array{courier: string, service: string, description: string, cost: int, etd: string}>
     *
     * @throws ShippingRateException
     */
    public function calculateRates(ShippingSetting $settings, string $destinationAreaId, int $weightGrams): array
    {
        $couriers = $settings->enabled_couriers ?? ['jne', 'jnt', 'sicepat'];

        try {
            $response = Http::asForm()->withHeaders(['key' => $settings->api_key])
                ->timeout(15)
                ->post("{$settings->baseApiUrl()}/calculate/domestic-cost", [
                    'origin' => $settings->origin_area_id,
                    'destination' => $destinationAreaId,
                    'weight' => max(1, $weightGrams),
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
}
