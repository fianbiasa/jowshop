<?php

namespace Tests\Feature\Settings;

use App\Models\BrandingSetting;
use App\Models\CdnSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CdnSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_cdn_settings(): void
    {
        $response = $this->get(route('cdn-settings.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_cdn_settings_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('cdn-settings.edit'));

        $response->assertOk();
    }

    public function test_cdn_settings_can_be_saved(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('cdn-settings.update'), [
            'pull_zone_url' => 'https://namazona.b-cdn.net',
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();

        $setting = CdnSetting::query()->firstOrFail();
        $this->assertSame('https://namazona.b-cdn.net', $setting->pull_zone_url);
        $this->assertTrue($setting->is_active);
    }

    public function test_pull_zone_url_is_required_when_activating(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('cdn-settings.update'), [
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('pull_zone_url');
    }

    public function test_cdn_can_be_disabled_without_clearing_the_url(): void
    {
        $user = User::factory()->create();
        CdnSetting::factory()->create(['pull_zone_url' => 'https://namazona.b-cdn.net', 'is_active' => true]);

        $response = $this->actingAs($user)->put(route('cdn-settings.update'), [
            'pull_zone_url' => 'https://namazona.b-cdn.net',
            'is_active' => 0,
        ]);

        $response->assertSessionHasNoErrors();

        $setting = CdnSetting::query()->firstOrFail();
        $this->assertSame('https://namazona.b-cdn.net', $setting->pull_zone_url);
        $this->assertFalse($setting->is_active);
    }

    public function test_when_active_storage_urls_use_the_cdn_domain(): void
    {
        CdnSetting::factory()->create(['pull_zone_url' => 'https://namazona.b-cdn.net']);
        BrandingSetting::factory()->create(['logo_path' => 'branding/logo.png']);

        $response = $this->get(route('home'));

        $response->assertInertia(fn ($page) => $page
            ->where('branding.logoUrl', 'https://namazona.b-cdn.net/storage/branding/logo.png')
        );
    }

    public function test_when_active_built_assets_are_served_from_the_cdn_domain(): void
    {
        CdnSetting::factory()->create(['pull_zone_url' => 'https://namazona.b-cdn.net']);

        $html = $this->get(route('home'))->getContent();

        $this->assertStringContainsString('https://namazona.b-cdn.net/build/', $html);
    }

    public function test_when_inactive_urls_stay_on_the_app_domain(): void
    {
        CdnSetting::factory()->create(['pull_zone_url' => 'https://namazona.b-cdn.net', 'is_active' => false]);
        BrandingSetting::factory()->create(['logo_path' => 'branding/logo.png']);

        $response = $this->get(route('home'));

        $response->assertInertia(fn ($page) => $page
            ->where('branding.logoUrl', Storage::disk('public')->url('branding/logo.png'))
        );
    }
}
