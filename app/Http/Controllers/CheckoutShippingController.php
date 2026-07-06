<?php

namespace App\Http\Controllers;

use App\Concerns\ManagesCheckoutSession;
use App\Enums\FunnelStatus;
use App\Enums\ShippingProvider;
use App\Exceptions\ShippingRateException;
use App\Models\Funnel;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingArea;
use App\Models\ShippingSetting;
use App\Services\ShippingRateClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CheckoutShippingController extends Controller
{
    use ManagesCheckoutSession;

    /**
     * Search the destination area used by the checkout form to help the
     * customer pick their delivery area. For Komerce/RajaOngkir, searches
     * the local area mirror first (no API call, no quota used) and only
     * falls back to the live provider search while local coverage is
     * incomplete (e.g. mid-import). The local mirror holds RajaOngkir-shaped
     * ids only, so it's skipped entirely for Biteship — those ids wouldn't
     * mean anything to Biteship's rate calculation anyway.
     */
    public function search(Request $request, Funnel $funnel, ShippingRateClient $client): JsonResponse
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
     * Show the available shipping methods for the in-progress order.
     */
    public function show(Request $request, Funnel $funnel, ShippingRateClient $client): Response
    {
        $this->ensurePublished($funnel);

        $order = $this->orderWithPhysicalItems($request, $funnel);

        $settings = ShippingSetting::query()->where('is_active', true)->first();

        abort_if($settings === null, 503, 'Pengiriman belum dikonfigurasi.');

        $address = $order->address;

        abort_if($address === null || $address->destination_area_id === null, 422, 'Alamat tujuan tidak lengkap.');

        try {
            $rates = $client->calculateRates($settings, $order, $address->destination_area_id);
        } catch (ShippingRateException) {
            $rates = [];
        }

        return Inertia::render('public/checkout-shipping', [
            'funnel' => ['name' => $funnel->name, 'slug' => $funnel->slug],
            'rates' => $rates,
        ]);
    }

    /**
     * Save the chosen shipping method, recomputing its cost server-side to
     * prevent a tampered price being submitted from the client.
     */
    public function store(Request $request, Funnel $funnel, ShippingRateClient $client): RedirectResponse
    {
        $this->ensurePublished($funnel);

        $validated = $request->validate([
            'courier' => ['required', 'string'],
            'service' => ['required', 'string'],
        ]);

        $order = $this->orderWithPhysicalItems($request, $funnel);

        $settings = ShippingSetting::query()->where('is_active', true)->first();

        abort_if($settings === null, 503, 'Pengiriman belum dikonfigurasi.');

        $address = $order->address;

        abort_if($address === null || $address->destination_area_id === null, 422, 'Alamat tujuan tidak lengkap.');

        try {
            $rates = $client->calculateRates($settings, $order, $address->destination_area_id);
        } catch (ShippingRateException $exception) {
            report($exception);

            throw ValidationException::withMessages(['courier' => 'Tidak bisa menghitung ongkir saat ini, silakan coba lagi.']);
        }

        $selected = collect($rates)->first(
            fn (array $rate) => $rate['courier'] === $validated['courier'] && $rate['service'] === $validated['service'],
        );

        if ($selected === null) {
            throw ValidationException::withMessages(['courier' => 'Metode pengiriman tidak valid, silakan pilih ulang.']);
        }

        $shipment = Shipment::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'courier' => $selected['courier'],
                'service' => $selected['service'],
                'cost' => $selected['cost'],
            ],
        );

        $order->update(['shipping_cost' => $shipment->cost]);
        $order->recalculateTotals();

        return to_route('public.checkout.pay', $funnel);
    }

    private function ensurePublished(Funnel $funnel): void
    {
        if ($funnel->status !== FunnelStatus::Published) {
            throw new NotFoundHttpException;
        }
    }

    private function orderWithPhysicalItems(Request $request, Funnel $funnel): Order
    {
        $orderId = $request->session()->get($this->orderSessionKey($funnel));

        if (! is_int($orderId)) {
            throw new NotFoundHttpException;
        }

        $order = Order::query()->with(['address', 'items.product'])->findOrFail($orderId);

        abort_if($order->totalPhysicalWeightGrams() <= 0, 404);

        return $order;
    }
}
