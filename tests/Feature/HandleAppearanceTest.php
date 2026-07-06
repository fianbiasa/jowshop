<?php

namespace Tests\Feature;

use App\Models\Funnel;
use App\Models\Salespage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The appearance (light/dark) feature was removed entirely — the whole app
 * is light-only now. These tests pin that: no page ever renders the dark
 * class, and every page declares a light-only color-scheme so browsers'
 * own "force dark" features (Android Chrome in particular) don't invert
 * the page either.
 */
class HandleAppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_salespage_always_renders_light(): void
    {
        $funnel = Funnel::factory()->published()->create(['slug' => 'kopi-robusta']);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $response = $this->withUnencryptedCookie('appearance', 'dark')->get('/f/kopi-robusta');

        $response->assertOk();
        $response->assertDontSee('class="dark"', false);
        $response->assertSee('name="color-scheme" content="light"', false);
    }

    public function test_order_lookup_form_always_renders_light(): void
    {
        $response = $this->withUnencryptedCookie('appearance', 'dark')->get(route('order-lookup.create'));

        $response->assertOk();
        $response->assertDontSee('class="dark"', false);
        $response->assertSee('name="color-scheme" content="light"', false);
    }

    public function test_admin_dashboard_always_renders_light(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withUnencryptedCookie('appearance', 'dark')
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('class="dark"', false);
        $response->assertSee('name="color-scheme" content="light"', false);
    }
}
