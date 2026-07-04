<?php

namespace Tests\Feature\Settings;

use App\Models\ShippingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShippingSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_shipping_settings(): void
    {
        $response = $this->get(route('shipping-settings.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_shipping_settings_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('shipping-settings.edit'));

        $response->assertOk();
    }

    public function test_shipping_settings_can_be_saved_with_encrypted_api_key(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('shipping-settings.update'), [
            'provider' => 'komerce',
            'api_key' => 'super-secret-key',
            'origin_area_id' => '17549',
            'origin_label' => 'Kebayoran Baru, Jakarta Selatan, DKI Jakarta',
            'enabled_couriers' => 'jne, jnt , sicepat',
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();

        $setting = ShippingSetting::query()->firstOrFail();
        $this->assertSame('super-secret-key', $setting->api_key);
        $this->assertSame(['jne', 'jnt', 'sicepat'], $setting->enabled_couriers);

        $rawColumn = DB::table('shipping_settings')->value('api_key');
        $this->assertStringNotContainsString('super-secret-key', $rawColumn);
    }
}
