<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Models\WhatsAppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WhatsAppSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_whatsapp_settings(): void
    {
        $response = $this->get(route('whatsapp-settings.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_whatsapp_settings_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('whatsapp-settings.edit'));

        $response->assertOk();
    }

    public function test_whatsapp_settings_can_be_saved_with_encrypted_api_key(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('whatsapp-settings.update'), [
            'api_key' => 'super-secret-device-key',
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();

        $setting = WhatsAppSetting::query()->firstOrFail();
        $this->assertSame('super-secret-device-key', $setting->api_key);
        $this->assertTrue($setting->is_active);

        $rawColumn = DB::table('whatsapp_settings')->value('api_key');
        $this->assertStringNotContainsString('super-secret-device-key', $rawColumn);
    }

    public function test_saving_again_updates_the_existing_setting(): void
    {
        $user = User::factory()->create();
        WhatsAppSetting::factory()->create(['api_key' => 'old-key']);

        $response = $this->actingAs($user)->put(route('whatsapp-settings.update'), [
            'api_key' => 'new-key',
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame(1, WhatsAppSetting::query()->count());
        $this->assertSame('new-key', WhatsAppSetting::query()->firstOrFail()->api_key);
    }
}
