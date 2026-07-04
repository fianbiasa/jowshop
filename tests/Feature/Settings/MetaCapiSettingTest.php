<?php

namespace Tests\Feature\Settings;

use App\Models\MetaCapiSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MetaCapiSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_meta_capi_settings(): void
    {
        $response = $this->get(route('meta-capi-settings.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_meta_capi_settings_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('meta-capi-settings.edit'));

        $response->assertOk();
    }

    public function test_meta_capi_settings_can_be_saved_with_encrypted_access_token(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('meta-capi-settings.update'), [
            'pixel_id' => '1234567890',
            'access_token' => 'super-secret-token',
            'test_event_code' => 'TEST12345',
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();

        $setting = MetaCapiSetting::query()->firstOrFail();
        $this->assertSame('super-secret-token', $setting->access_token);
        $this->assertSame('1234567890', $setting->pixel_id);

        $rawColumn = DB::table('meta_capi_settings')->value('access_token');
        $this->assertStringNotContainsString('super-secret-token', $rawColumn);
    }
}
