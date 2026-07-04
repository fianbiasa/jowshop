<?php

namespace Tests\Feature;

use App\Models\Funnel;
use App\Models\Product;
use App\Models\Salespage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_funnel_routes_are_throttled(): void
    {
        $product = Product::factory()->digital()->published()->create();
        $funnel = Funnel::factory()->published()->create(['product_id' => $product->id]);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        for ($i = 0; $i < 30; $i++) {
            $response = $this->get("/f/{$funnel->slug}");
            $response->assertOk();
        }

        $response = $this->get("/f/{$funnel->slug}");
        $response->assertStatus(429);
    }

    public function test_order_lookup_is_throttled_tighter_than_other_public_routes(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $response = $this->get('/pesanan-saya');
            $response->assertOk();
        }

        $response = $this->get('/pesanan-saya');
        $response->assertStatus(429);
    }
}
