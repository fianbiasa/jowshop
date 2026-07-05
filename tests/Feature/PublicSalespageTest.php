<?php

namespace Tests\Feature;

use App\Enums\FunnelStatus;
use App\Models\Funnel;
use App\Models\MetaCapiSetting;
use App\Models\Salespage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSalespageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpublished_funnel_returns_404(): void
    {
        $funnel = Funnel::factory()->create(['slug' => 'kopi-robusta', 'status' => FunnelStatus::Draft]);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $response = $this->get('/f/kopi-robusta');

        $response->assertNotFound();
    }

    public function test_published_funnel_with_unpublished_salespage_returns_404(): void
    {
        $funnel = Funnel::factory()->published()->create(['slug' => 'kopi-robusta']);
        Salespage::factory()->create(['funnel_id' => $funnel->id]);

        $response = $this->get('/f/kopi-robusta');

        $response->assertNotFound();
    }

    public function test_published_funnel_with_published_salespage_is_visible(): void
    {
        $funnel = Funnel::factory()->published()->create(['slug' => 'kopi-robusta']);
        Salespage::factory()->published()->create([
            'funnel_id' => $funnel->id,
            'title' => 'Kopi Robusta Terbaik',
        ]);

        $response = $this->get('/f/kopi-robusta');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('public/salespage-view')
            ->where('salespage.title', 'Kopi Robusta Terbaik')
        );
    }

    public function test_unknown_slug_returns_404(): void
    {
        $response = $this->get('/f/tidak-ada');

        $response->assertNotFound();
    }

    public function test_browser_pixel_falls_back_to_global_meta_capi_settings_when_funnel_has_no_pixel_id(): void
    {
        MetaCapiSetting::factory()->create(['pixel_id' => '999888777', 'is_active' => true]);
        $funnel = Funnel::factory()->published()->create(['slug' => 'kopi-robusta']);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $response = $this->get('/f/kopi-robusta');

        $response->assertInertia(fn ($page) => $page->where('metaPixel.pixel_id', '999888777'));
    }

    public function test_browser_pixel_prefers_the_funnels_own_pixel_id_over_the_global_setting(): void
    {
        MetaCapiSetting::factory()->create(['pixel_id' => '999888777', 'is_active' => true]);
        $funnel = Funnel::factory()->published()->create([
            'slug' => 'kopi-robusta',
            'pixel_settings' => ['fb_pixel_id' => '111222333'],
        ]);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $response = $this->get('/f/kopi-robusta');

        $response->assertInertia(fn ($page) => $page->where('metaPixel.pixel_id', '111222333'));
    }

    public function test_browser_pixel_is_absent_when_no_pixel_is_configured_anywhere(): void
    {
        $funnel = Funnel::factory()->published()->create(['slug' => 'kopi-robusta']);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $response = $this->get('/f/kopi-robusta');

        $response->assertInertia(fn ($page) => $page->where('metaPixel', null));
    }

    /**
     * The public storefront isn't behind the `auth` middleware, but an admin
     * browsing it in the same session shouldn't have their account (name,
     * email, 2FA status) embedded in a page anyone can view-source.
     */
    public function test_logged_in_admins_account_is_not_exposed_on_the_public_page(): void
    {
        $admin = User::factory()->create(['email' => 'admin@jowshop.web.id']);
        $funnel = Funnel::factory()->published()->create(['slug' => 'kopi-robusta']);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $response = $this->actingAs($admin)->get('/f/kopi-robusta');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('auth.user', null));
    }
}
