<?php

namespace Tests\Feature;

use App\Enums\ShippingProvider;
use App\Models\ShippingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_is_displayed(): void
    {
        $response = $this->get(route('tracking.create'));

        $response->assertOk();
    }

    public function test_a_successful_lookup_shows_the_status_and_history(): void
    {
        ShippingSetting::factory()->create(['provider' => ShippingProvider::Biteship]);

        Http::fake(['*/trackings/*' => Http::response([
            'success' => true,
            'status' => 'delivered',
            'history' => [
                ['status' => 'confirmed', 'note' => 'Order confirmed', 'updated_at' => '2026-07-01T10:00:00+07:00'],
            ],
        ], 200)]);

        $response = $this->post(route('tracking.store'), [
            'courier' => 'jne',
            'tracking_number' => '020170030469926',
        ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('tracking.status', 'delivered')
            ->has('tracking.history', 1)
        );
    }

    public function test_a_failed_lookup_shows_a_generic_error_not_the_raw_provider_response(): void
    {
        ShippingSetting::factory()->create(['provider' => ShippingProvider::Biteship]);

        Http::fake(['*/trackings/*' => Http::response([
            'success' => false,
            'error' => 'Failed to get tracking information. It\'s either invalid or expired. Please check again',
            'code' => 40003001,
        ], 400)]);

        $response = $this->post(route('tracking.store'), [
            'courier' => 'jne',
            'tracking_number' => '020170030469926',
        ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('error'));
        $response->assertDontSee('40003001');
        $response->assertDontSee('Failed to get tracking information', false);
    }

    public function test_lookup_is_not_supported_when_provider_is_not_biteship(): void
    {
        // Default factory provider is Komerce.
        ShippingSetting::factory()->create();

        Http::fake();

        $response = $this->post(route('tracking.store'), [
            'courier' => 'jne',
            'tracking_number' => '020170030469926',
        ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('error'));
        Http::assertNothingSent();
    }
}
