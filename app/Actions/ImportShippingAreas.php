<?php

namespace App\Actions;

use App\Models\ShippingArea;
use App\Models\ShippingSetting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportShippingAreas
{
    /**
     * Mirrors RajaOngkir/Komerce's province → city → district → sub-district
     * hierarchy into the local `shipping_areas` table, so checkout
     * destination search never needs to call the provider live. Safe to
     * interrupt and re-run: a district's sub-districts are only ever
     * fetched and inserted as a whole, so `rajaongkir_district_id` existing
     * locally means that district is already fully imported and gets
     * skipped on the next run.
     *
     * A city/district-level failure is logged and skipped (retried on the
     * next run, since nothing gets written for it). A 429 (daily quota
     * exhausted) aborts the whole run immediately instead — every
     * subsequent request would fail the same way until the quota resets, so
     * there's no point burning through the rest of the tree.
     *
     * @return array{provinces: int, districts_imported: int, districts_skipped: int, areas_upserted: int}
     *
     * @throws RequestException if the provider's daily quota is exhausted (HTTP 429), or the
     *                          very first province request itself fails
     */
    public function __invoke(ShippingSetting $settings, int $delayMs = 250, ?callable $onProgress = null): array
    {
        $base = $settings->baseApiUrl();
        $headers = ['key' => $settings->api_key];

        $provinces = $this->fetchData($base, $headers, '/destination/province');

        $stats = ['provinces' => count($provinces), 'districts_imported' => 0, 'districts_skipped' => 0, 'areas_upserted' => 0];

        foreach ($provinces as $province) {
            try {
                $cities = $this->fetchData($base, $headers, "/destination/city/{$province['id']}");
            } catch (\Throwable $exception) {
                $this->abortIfQuotaExhausted($exception);

                Log::warning('Failed to fetch shipping cities for province.', [
                    'province_id' => $province['id'],
                    'province_name' => $province['name'] ?? null,
                    'error' => $exception->getMessage(),
                ]);

                continue;
            }

            foreach ($cities as $city) {
                try {
                    $districts = $this->fetchData($base, $headers, "/destination/district/{$city['id']}");
                } catch (\Throwable $exception) {
                    $this->abortIfQuotaExhausted($exception);

                    Log::warning('Failed to fetch shipping districts for city.', [
                        'city_id' => $city['id'],
                        'city_name' => $city['name'] ?? null,
                        'error' => $exception->getMessage(),
                    ]);

                    continue;
                }

                foreach ($districts as $district) {
                    if (ShippingArea::query()->where('rajaongkir_district_id', $district['id'])->exists()) {
                        $stats['districts_skipped']++;

                        continue;
                    }

                    try {
                        $subdistricts = $this->fetchData($base, $headers, "/destination/sub-district/{$district['id']}");

                        $rows = array_map(fn (array $sub): array => [
                            'id' => $sub['id'],
                            'rajaongkir_province_id' => $province['id'],
                            'rajaongkir_city_id' => $city['id'],
                            'rajaongkir_district_id' => $district['id'],
                            'province_name' => $province['name'],
                            'city_name' => $city['name'],
                            'district_name' => $district['name'],
                            'subdistrict_name' => $sub['name'],
                            'zip_code' => $sub['zip_code'],
                            'label' => "{$sub['name']}, {$district['name']}, {$city['name']}, {$province['name']}, {$sub['zip_code']}",
                            'created_at' => now(),
                            'updated_at' => now(),
                        ], $subdistricts);

                        if ($rows !== []) {
                            ShippingArea::query()->upsert($rows, ['id']);
                        }

                        $stats['districts_imported']++;
                        $stats['areas_upserted'] += count($rows);
                    } catch (\Throwable $exception) {
                        $this->abortIfQuotaExhausted($exception);

                        Log::warning('Failed to import shipping sub-districts for district.', [
                            'district_id' => $district['id'],
                            'district_name' => $district['name'] ?? null,
                            'error' => $exception->getMessage(),
                        ]);
                    }

                    if ($onProgress !== null) {
                        $onProgress($stats);
                    }

                    usleep($delayMs * 1000);
                }
            }
        }

        return $stats;
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<int, array<string, mixed>>
     *
     * @throws RequestException
     */
    private function fetchData(string $base, array $headers, string $path): array
    {
        return Http::withHeaders($headers)->timeout(30)->get("{$base}{$path}")->throw()->json('data') ?? [];
    }

    /**
     * @throws RequestException
     */
    private function abortIfQuotaExhausted(\Throwable $exception): void
    {
        if ($exception instanceof RequestException && $exception->response->status() === 429) {
            throw $exception;
        }
    }
}
