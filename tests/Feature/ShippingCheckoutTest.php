<?php

namespace Tests\Feature;

use App\Enums\ShippingProvider;
use App\Models\Funnel;
use App\Models\Order;
use App\Models\Product;
use App\Models\Salespage;
use App\Models\Shipment;
use App\Models\ShippingArea;
use App\Models\ShippingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShippingCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function publishedPhysicalFunnel(): Funnel
    {
        $product = Product::factory()->physical()->published()->create([
            'price' => 45000,
            'weight_grams' => 500,
        ]);

        $funnel = Funnel::factory()->published()->create([
            'product_id' => $product->id,
            'slug' => 'kopi',
        ]);

        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        return $funnel;
    }

    private function physicalCheckoutPayload(): array
    {
        return [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'district' => 'Kebayoran Baru',
            'postal_code' => '12110',
            'destination_area_id' => '17550',
            'destination_label' => 'Kebayoran Baru, Jakarta Selatan, DKI Jakarta, 12110',
            'address_line' => 'Jl. Sudirman No. 1',
        ];
    }

    private function fakeDestinationSearch(): void
    {
        Http::fake([
            '*/destination/domestic-destination*' => Http::response([
                'data' => [
                    ['id' => '17550', 'label' => 'Kebayoran Baru, Jakarta Selatan, DKI Jakarta, 12110'],
                    ['id' => '17551', 'label' => 'Kebayoran Lama, Jakarta Selatan, DKI Jakarta, 12220'],
                ],
            ], 200),
        ]);
    }

    private function fakeCostCalculation(): void
    {
        Http::fake([
            '*/calculate/domestic-cost' => Http::response([
                'data' => [
                    ['code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 15000, 'etd' => '2-3'],
                    ['code' => 'jne', 'service' => 'YES', 'description' => 'Yakin Esok Sampai', 'cost' => 25000, 'etd' => '1'],
                ],
            ], 200),
        ]);
    }

    private function fakeBiteshipDestinationSearch(): void
    {
        Http::fake([
            '*/maps/areas*' => Http::response([
                'success' => true,
                'areas' => [
                    ['id' => 'IDNP6IDNC147IDND832IDZ10310', 'name' => 'Menteng, Jakarta Pusat, DKI Jakarta. 10310'],
                    ['id' => 'IDNP6IDNC147IDND832IDZ10320', 'name' => 'Menteng, Jakarta Pusat, DKI Jakarta. 10320'],
                ],
            ], 200),
        ]);
    }

    private function fakeBiteshipCostCalculation(): void
    {
        Http::fake([
            '*/rates/couriers' => Http::response([
                'success' => true,
                'pricing' => [
                    ['courier_code' => 'jne', 'courier_service_code' => 'reg', 'courier_service_name' => 'Reguler', 'description' => 'Reguler', 'price' => 15000, 'duration' => '2-3 days'],
                    ['courier_code' => 'jne', 'courier_service_code' => 'yes', 'courier_service_name' => 'YES', 'description' => 'Yakin Esok Sampai', 'price' => 25000, 'duration' => '1 day'],
                ],
            ], 200),
        ]);
    }

    public function test_destination_search_returns_local_results_without_calling_the_provider(): void
    {
        ShippingArea::factory()->create(['label' => 'KEBAYORAN BARU, KEBAYORAN BARU, JAKARTA SELATAN, DKI JAKARTA, 12110']);
        ShippingArea::factory()->create(['label' => 'KEBAYORAN LAMA, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12220']);
        ShippingArea::factory()->create(['label' => 'MENTENG, MENTENG, JAKARTA PUSAT, DKI JAKARTA, 10310']);
        Http::fake();

        $funnel = $this->publishedPhysicalFunnel();

        $response = $this->get("/f/{$funnel->slug}/checkout/destinations?q=Kebayoran");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        Http::assertNothingSent();
    }

    public function test_destination_search_falls_back_to_the_provider_when_local_coverage_is_missing(): void
    {
        ShippingSetting::factory()->create();
        ShippingArea::factory()->create(['label' => 'MENTENG, MENTENG, JAKARTA PUSAT, DKI JAKARTA, 10310']);
        $this->fakeDestinationSearch();

        $funnel = $this->publishedPhysicalFunnel();

        $response = $this->get("/f/{$funnel->slug}/checkout/destinations?q=Kebayoran");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_destination_search_skips_the_local_cache_entirely_when_biteship_is_active(): void
    {
        ShippingSetting::factory()->create(['provider' => ShippingProvider::Biteship]);
        // Stale Komerce/RajaOngkir cache data that happens to match the query —
        // must never be returned while Biteship is the active provider, since
        // its ids mean nothing to Biteship's rate calculation.
        ShippingArea::factory()->create(['label' => 'KEBAYORAN BARU, KEBAYORAN BARU, JAKARTA SELATAN, DKI JAKARTA, 12110']);
        $this->fakeBiteshipDestinationSearch();

        $funnel = $this->publishedPhysicalFunnel();

        $response = $this->get("/f/{$funnel->slug}/checkout/destinations?q=Menteng");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => 'IDNP6IDNC147IDND832IDZ10310', 'label' => 'Menteng, Jakarta Pusat, DKI Jakarta. 10310']);
        Http::assertSent(fn ($request) => str_contains((string) $request->url(), '/maps/areas'));
    }

    public function test_destination_search_returns_empty_when_provider_is_unreachable(): void
    {
        ShippingSetting::factory()->create();
        Http::fake(['*/domestic-destination*' => Http::failedConnection()]);

        $funnel = $this->publishedPhysicalFunnel();

        $response = $this->get("/f/{$funnel->slug}/checkout/destinations?q=Kebayoran");

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_destination_search_returns_empty_for_short_query(): void
    {
        ShippingSetting::factory()->create();

        $funnel = $this->publishedPhysicalFunnel();

        $response = $this->get("/f/{$funnel->slug}/checkout/destinations?q=ke");

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_physical_checkout_without_bumps_redirects_to_shipping_selection(): void
    {
        $funnel = $this->publishedPhysicalFunnel();

        $response = $this->post("/f/{$funnel->slug}/checkout", $this->physicalCheckoutPayload());

        $response->assertRedirect("/f/{$funnel->slug}/checkout/pengiriman");
    }

    public function test_digital_checkout_skips_shipping_selection(): void
    {
        $product = Product::factory()->digital()->published()->create(['price' => 45000]);
        $funnel = Funnel::factory()->published()->create(['product_id' => $product->id, 'slug' => 'ebook']);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $response = $this->post("/f/{$funnel->slug}/checkout", [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
        ]);

        $response->assertRedirect("/f/{$funnel->slug}/checkout/bayar");
    }

    public function test_shipping_selection_without_settings_returns_503(): void
    {
        $funnel = $this->publishedPhysicalFunnel();
        $this->post("/f/{$funnel->slug}/checkout", $this->physicalCheckoutPayload());

        $response = $this->get("/f/{$funnel->slug}/checkout/pengiriman");

        $response->assertStatus(503);
    }

    public function test_shipping_selection_page_displays_calculated_rates(): void
    {
        ShippingSetting::factory()->create();
        $this->fakeCostCalculation();

        $funnel = $this->publishedPhysicalFunnel();
        $this->post("/f/{$funnel->slug}/checkout", $this->physicalCheckoutPayload());

        $response = $this->get("/f/{$funnel->slug}/checkout/pengiriman");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('public/checkout-shipping')
            ->has('rates', 2)
        );
    }

    public function test_selecting_shipping_rate_creates_shipment_and_updates_total(): void
    {
        ShippingSetting::factory()->create();
        $this->fakeCostCalculation();

        $funnel = $this->publishedPhysicalFunnel();
        $this->post("/f/{$funnel->slug}/checkout", $this->physicalCheckoutPayload());
        $this->get("/f/{$funnel->slug}/checkout/pengiriman");

        $response = $this->post("/f/{$funnel->slug}/checkout/pengiriman", [
            'courier' => 'jne',
            'service' => 'YES',
        ]);

        $response->assertRedirect("/f/{$funnel->slug}/checkout/bayar");

        $order = Order::query()->firstOrFail();
        $shipment = Shipment::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame('jne', $shipment->courier);
        $this->assertSame('YES', $shipment->service);
        $this->assertSame('25000.00', $shipment->cost);
        $this->assertSame('70000.00', $order->total); // 45000 + 25000
    }

    public function test_selecting_a_rate_shows_friendly_error_when_provider_is_unreachable_on_submit(): void
    {
        ShippingSetting::factory()->create();
        $this->fakeCostCalculation();

        $funnel = $this->publishedPhysicalFunnel();
        $this->post("/f/{$funnel->slug}/checkout", $this->physicalCheckoutPayload());
        $this->get("/f/{$funnel->slug}/checkout/pengiriman");

        Http::fake(['*/calculate/domestic-cost' => Http::failedConnection()]);

        $response = $this->post("/f/{$funnel->slug}/checkout/pengiriman", [
            'courier' => 'jne',
            'service' => 'YES',
        ]);

        $response->assertSessionHasErrors('courier');
        $this->assertSame(0, Shipment::query()->count());
    }

    public function test_shipping_selection_page_displays_calculated_rates_from_biteship(): void
    {
        ShippingSetting::factory()->create(['provider' => ShippingProvider::Biteship]);
        $this->fakeBiteshipCostCalculation();

        $funnel = $this->publishedPhysicalFunnel();
        $this->post("/f/{$funnel->slug}/checkout", array_merge($this->physicalCheckoutPayload(), [
            'destination_area_id' => 'IDNP6IDNC147IDND832IDZ10310',
            'destination_label' => 'Menteng, Jakarta Pusat, DKI Jakarta. 10310',
        ]));

        $response = $this->get("/f/{$funnel->slug}/checkout/pengiriman");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('public/checkout-shipping')
            ->has('rates', 2)
        );
    }

    public function test_selecting_shipping_rate_creates_shipment_and_updates_total_with_biteship(): void
    {
        ShippingSetting::factory()->create(['provider' => ShippingProvider::Biteship]);
        $this->fakeBiteshipCostCalculation();

        $funnel = $this->publishedPhysicalFunnel();
        $this->post("/f/{$funnel->slug}/checkout", array_merge($this->physicalCheckoutPayload(), [
            'destination_area_id' => 'IDNP6IDNC147IDND832IDZ10310',
            'destination_label' => 'Menteng, Jakarta Pusat, DKI Jakarta. 10310',
        ]));
        $this->get("/f/{$funnel->slug}/checkout/pengiriman");

        $response = $this->post("/f/{$funnel->slug}/checkout/pengiriman", [
            'courier' => 'jne',
            'service' => 'yes',
        ]);

        $response->assertRedirect("/f/{$funnel->slug}/checkout/bayar");

        $order = Order::query()->firstOrFail();
        $shipment = Shipment::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame('jne', $shipment->courier);
        $this->assertSame('yes', $shipment->service);
        $this->assertSame('25000.00', $shipment->cost);
        $this->assertSame('70000.00', $order->total); // 45000 + 25000
    }

    public function test_selecting_a_rate_not_returned_by_the_provider_is_rejected(): void
    {
        ShippingSetting::factory()->create();
        $this->fakeCostCalculation();

        $funnel = $this->publishedPhysicalFunnel();
        $this->post("/f/{$funnel->slug}/checkout", $this->physicalCheckoutPayload());
        $this->get("/f/{$funnel->slug}/checkout/pengiriman");

        $response = $this->post("/f/{$funnel->slug}/checkout/pengiriman", [
            'courier' => 'sicepat',
            'service' => 'BEST',
        ]);

        $response->assertSessionHasErrors('courier');
        $this->assertSame(0, Shipment::query()->count());
    }
}
