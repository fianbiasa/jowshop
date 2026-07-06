<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\ShippingProvider;
use App\Exceptions\ShippingRateException;
use App\Models\Product;
use App\Models\ShippingArea;
use App\Models\ShippingSetting;
use App\Services\ShippingRateClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShippingEstimateController extends Controller
{
    /**
     * Show the public "Cek Ongkir" tool.
     */
    public function create(): Response
    {
        return Inertia::render('public/shipping-estimate', [
            'products' => $this->physicalProducts(),
        ]);
    }

    /**
     * Search the destination area — same local-cache-first, live-fallback
     * behavior as CheckoutShippingController::search(), just without a
     * funnel in scope since this tool isn't tied to any specific checkout.
     */
    public function searchDestinations(Request $request, ShippingRateClient $client): JsonResponse
    {
        $query = (string) $request->query('q', '');

        if (mb_strlen($query) < 3) {
            return response()->json(['data' => []]);
        }

        $settings = ShippingSetting::query()->where('is_active', true)->first();

        if ($settings === null || $settings->provider !== ShippingProvider::Biteship) {
            $local = ShippingArea::query()
                ->where('label', 'like', "%{$query}%")
                ->limit(10)
                ->get(['id', 'label']);

            if ($local->isNotEmpty()) {
                return response()->json(['data' => $local->map(fn (ShippingArea $area): array => [
                    'id' => (string) $area->id,
                    'label' => $area->label,
                ])]);
            }
        }

        if ($settings === null) {
            return response()->json(['data' => []]);
        }

        try {
            $results = $client->searchDestination($settings, $query);
        } catch (ShippingRateException) {
            $results = [];
        }

        return response()->json(['data' => $results]);
    }

    /**
     * Estimate shipping cost for a chosen product + destination.
     */
    public function store(Request $request, ShippingRateClient $client): Response
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'destination_area_id' => ['required', 'string'],
            'destination_label' => ['nullable', 'string'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);

        abort_if(! $product->isPhysical(), 422, 'Produk ini tidak memerlukan pengiriman.');

        $settings = ShippingSetting::query()->where('is_active', true)->first();

        abort_if($settings === null, 503, 'Pengiriman belum dikonfigurasi.');

        try {
            $rates = $client->calculateRatesForProduct($settings, $product, $validated['destination_area_id']);
        } catch (ShippingRateException) {
            $rates = [];
        }

        return Inertia::render('public/shipping-estimate', [
            'products' => $this->physicalProducts(),
            'rates' => $rates,
            'selectedProductId' => $product->id,
            'destinationLabel' => $validated['destination_label'] ?? null,
        ]);
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function physicalProducts(): array
    {
        return Product::query()
            ->where('status', ProductStatus::Published)
            ->where('type', ProductType::Physical)
            ->whereNotNull('weight_grams')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Product $product): array => ['id' => $product->id, 'name' => $product->name])
            ->all();
    }
}
