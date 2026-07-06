<?php

namespace Tests\Feature;

use App\Enums\ShippingProvider;
use App\Models\Product;
use App\Models\ShippingArea;
use App\Models\ShippingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicShippingEstimateTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_lists_published_physical_products_with_a_weight(): void
    {
        $physical = Product::factory()->physical()->published()->create(['weight_grams' => 500]);
        Product::factory()->digital()->published()->create();
        Product::factory()->physical()->create(['status' => 'draft', 'weight_grams' => 500]);

        $response = $this->get(route('shipping-estimate.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('products', 1)
            ->where('products.0.id', $physical->id)
        );
    }

    public function test_destination_search_works_without_a_funnel_in_scope(): void
    {
        ShippingArea::factory()->create(['label' => 'MENTENG, MENTENG, JAKARTA PUSAT, DKI JAKARTA, 10310']);
        Http::fake();

        $response = $this->get(route('shipping-estimate.destinations', ['q' => 'Menteng']));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        Http::assertNothingSent();
    }

    public function test_estimating_a_physical_product_returns_rates(): void
    {
        ShippingSetting::factory()->create();
        $product = Product::factory()->physical()->published()->create(['weight_grams' => 500]);

        Http::fake(['*/calculate/domestic-cost' => Http::response([
            'data' => [
                ['code' => 'jne', 'service' => 'reg', 'description' => 'Reguler', 'cost' => 15000, 'etd' => '2-3'],
            ],
        ], 200)]);

        $response = $this->post(route('shipping-estimate.store'), [
            'product_id' => $product->id,
            'destination_area_id' => '17550',
            'destination_label' => 'Kebayoran Baru, Jakarta Selatan, DKI Jakarta, 12110',
        ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('rates', 1)
            ->where('rates.0.courier', 'jne')
        );
    }

    public function test_estimating_a_digital_product_is_rejected(): void
    {
        ShippingSetting::factory()->create();
        $product = Product::factory()->digital()->published()->create();

        $response = $this->post(route('shipping-estimate.store'), [
            'product_id' => $product->id,
            'destination_area_id' => '17550',
        ]);

        $response->assertStatus(422);
    }

    public function test_estimating_without_active_shipping_settings_returns_503(): void
    {
        $product = Product::factory()->physical()->published()->create(['weight_grams' => 500]);

        $response = $this->post(route('shipping-estimate.store'), [
            'product_id' => $product->id,
            'destination_area_id' => '17550',
        ]);

        $response->assertStatus(503);
    }

    public function test_biteship_provider_sends_correct_item_weight_from_the_product(): void
    {
        ShippingSetting::factory()->create(['provider' => ShippingProvider::Biteship]);
        $product = Product::factory()->physical()->published()->create(['weight_grams' => 750, 'price' => 60000]);

        Http::fake(['*/rates/couriers' => Http::response(['pricing' => []], 200)]);

        $this->post(route('shipping-estimate.store'), [
            'product_id' => $product->id,
            'destination_area_id' => 'IDNP6IDNC147IDND832IDZ10310',
        ]);

        Http::assertSent(fn ($request) => str_contains((string) $request->url(), '/rates/couriers')
            && $request['items'][0]['weight'] === 750);
    }
}
