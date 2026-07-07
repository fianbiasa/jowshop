<?php

namespace Tests\Feature\Settings;

use App\Models\BrandingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_branding_settings(): void
    {
        $response = $this->get(route('branding-settings.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_branding_settings_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('branding-settings.edit'));

        $response->assertOk();
    }

    public function test_uploading_a_logo_stores_the_file_and_persists_a_url(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('branding-settings.update'), [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response->assertSessionHasNoErrors();

        $setting = BrandingSetting::query()->firstOrFail();
        $this->assertNotNull($setting->logo_path);
        Storage::disk('public')->assertExists($setting->logo_path);
        $this->assertNotNull($setting->logoUrl());
    }

    public function test_uploading_a_replacement_logo_deletes_the_old_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('branding-settings.update'), [
            'logo' => UploadedFile::fake()->image('first.png'),
        ]);
        $originalPath = BrandingSetting::query()->firstOrFail()->logo_path;

        $this->actingAs($user)->put(route('branding-settings.update'), [
            'logo' => UploadedFile::fake()->image('second.png'),
        ]);

        $setting = BrandingSetting::query()->firstOrFail();
        $this->assertNotSame($originalPath, $setting->logo_path);
        Storage::disk('public')->assertMissing($originalPath);
        Storage::disk('public')->assertExists($setting->logo_path);
    }

    public function test_remove_logo_clears_the_logo_path(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('branding-settings.update'), [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);
        $originalPath = BrandingSetting::query()->firstOrFail()->logo_path;

        $response = $this->actingAs($user)->put(route('branding-settings.update'), [
            'remove_logo' => 1,
        ]);

        $response->assertSessionHasNoErrors();

        $setting = BrandingSetting::query()->firstOrFail();
        $this->assertNull($setting->logo_path);
        Storage::disk('public')->assertMissing($originalPath);
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('branding-settings.update'), [
            'logo' => UploadedFile::fake()->create('logo.pdf', 100),
        ]);

        $response->assertSessionHasErrors('logo');
        $this->assertNull(BrandingSetting::query()->first());
    }

    public function test_branding_shared_prop_is_available_on_admin_and_public_pages(): void
    {
        Storage::fake('public');
        BrandingSetting::factory()->create(['logo_path' => 'branding/logo.png']);
        $user = User::factory()->create();

        $adminResponse = $this->actingAs($user)->get(route('dashboard'));
        $adminResponse->assertInertia(fn ($page) => $page
            ->where('branding.logoUrl', Storage::disk('public')->url('branding/logo.png'))
            ->where('branding.siteName', config('app.name'))
        );

        $publicResponse = $this->get(route('home'));
        $publicResponse->assertInertia(fn ($page) => $page
            ->where('branding.logoUrl', Storage::disk('public')->url('branding/logo.png'))
        );
    }
}
