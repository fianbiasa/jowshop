<?php

namespace Tests\Feature\Settings;

use App\Enums\ShippingProvider;
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

    public function test_shipping_settings_can_be_saved_with_biteship_as_the_provider(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('shipping-settings.update'), [
            'provider' => 'biteship',
            'api_key' => 'biteship_test.super-secret-key',
            'origin_area_id' => 'IDNP6IDNC147IDND832IDZ10310',
            'origin_label' => 'Menteng, Jakarta Pusat, DKI Jakarta',
            'enabled_couriers' => 'jne,jnt,sicepat',
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();

        $setting = ShippingSetting::query()->firstOrFail();
        $this->assertSame(ShippingProvider::Biteship, $setting->provider);
    }

    public function test_auto_book_shipping_requires_origin_contact_and_address_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('shipping-settings.update'), [
            'provider' => 'biteship',
            'api_key' => 'biteship_test.super-secret-key',
            'origin_area_id' => 'IDNP6IDNC147IDND832IDZ10310',
            'auto_book_shipping' => 1,
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors([
            'origin_contact_name',
            'origin_contact_phone',
            'origin_address',
            'origin_postal_code',
        ]);
    }

    public function test_auto_book_shipping_can_be_saved_with_origin_contact_and_address_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('shipping-settings.update'), [
            'provider' => 'biteship',
            'api_key' => 'biteship_test.super-secret-key',
            'origin_area_id' => 'IDNP6IDNC147IDND832IDZ10310',
            'origin_contact_name' => 'Budi Toko',
            'origin_contact_phone' => '081200000000',
            'origin_address' => 'Jl. Gudang No. 1',
            'origin_postal_code' => '28125',
            'auto_book_shipping' => 1,
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();

        $setting = ShippingSetting::query()->firstOrFail();
        $this->assertTrue($setting->auto_book_shipping);
        $this->assertSame('Budi Toko', $setting->origin_contact_name);
    }

    public function test_auto_book_shipping_is_not_required_when_toggle_is_off(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('shipping-settings.update'), [
            'provider' => 'komerce',
            'api_key' => 'super-secret-key',
            'origin_area_id' => '17549',
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();

        $setting = ShippingSetting::query()->firstOrFail();
        $this->assertFalse($setting->auto_book_shipping);
    }
}
