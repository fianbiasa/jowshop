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
        BrandingSetting::factory()->create([
            'logo_path' => 'branding/logo.png',
            'address' => 'Jl. Contoh No. 1, Jakarta',
            'email' => 'halo@bisnismu.com',
            'phone' => '0812345678',
        ]);
        $user = User::factory()->create();

        $adminResponse = $this->actingAs($user)->get(route('dashboard'));
        $adminResponse->assertInertia(fn ($page) => $page
            ->where('branding.logoUrl', Storage::disk('public')->url('branding/logo.png'))
            ->where('branding.siteName', config('app.name'))
            ->where('branding.address', 'Jl. Contoh No. 1, Jakarta')
            ->where('branding.email', 'halo@bisnismu.com')
            ->where('branding.phone', '0812345678')
        );

        $publicResponse = $this->get(route('home'));
        $publicResponse->assertInertia(fn ($page) => $page
            ->where('branding.logoUrl', Storage::disk('public')->url('branding/logo.png'))
            ->where('branding.address', 'Jl. Contoh No. 1, Jakarta')
            ->where('branding.email', 'halo@bisnismu.com')
            ->where('branding.phone', '0812345678')
        );
    }

    public function test_branding_email_falls_back_to_mail_from_address_when_unset(): void
    {
        $response = $this->get(route('home'));

        $response->assertInertia(fn ($page) => $page
            ->where('branding.email', config('mail.from.address'))
        );
    }

    public function test_updating_contact_info_persists_address_email_and_phone(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('branding-settings.update'), [
            'address' => 'Jl. Contoh No. 1, Jakarta',
            'email' => 'halo@bisnismu.com',
            'phone' => '0812345678',
        ]);

        $response->assertSessionHasNoErrors();

        $setting = BrandingSetting::query()->firstOrFail();
        $this->assertSame('Jl. Contoh No. 1, Jakarta', $setting->address);
        $this->assertSame('halo@bisnismu.com', $setting->email);
        $this->assertSame('0812345678', $setting->phone);
    }

    public function test_updating_the_logo_does_not_clear_existing_contact_info(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        BrandingSetting::factory()->create([
            'address' => 'Jl. Contoh No. 1, Jakarta',
            'email' => 'halo@bisnismu.com',
            'phone' => '0812345678',
        ]);

        $this->actingAs($user)->put(route('branding-settings.update'), [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $setting = BrandingSetting::query()->firstOrFail();
        $this->assertSame('Jl. Contoh No. 1, Jakarta', $setting->address);
        $this->assertSame('halo@bisnismu.com', $setting->email);
        $this->assertSame('0812345678', $setting->phone);
    }

    public function test_updating_contact_info_does_not_clear_the_existing_logo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        BrandingSetting::factory()->create(['logo_path' => 'branding/logo.png']);

        $this->actingAs($user)->put(route('branding-settings.update'), [
            'email' => 'halo@bisnismu.com',
        ]);

        $setting = BrandingSetting::query()->firstOrFail();
        $this->assertSame('branding/logo.png', $setting->logo_path);
        $this->assertSame('halo@bisnismu.com', $setting->email);
    }

    public function test_invalid_contact_email_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('branding-settings.update'), [
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
