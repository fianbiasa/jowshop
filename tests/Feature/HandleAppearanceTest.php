<?php

namespace Tests\Feature;

use App\Models\Funnel;
use App\Models\Salespage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandleAppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_salespage_ignores_the_dark_appearance_cookie(): void
    {
        $funnel = Funnel::factory()->published()->create(['slug' => 'kopi-robusta']);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $response = $this->withUnencryptedCookie('appearance', 'dark')->get('/f/kopi-robusta');

        $response->assertOk();
        $response->assertDontSee('class="dark"', false);
    }

    public function test_order_lookup_form_ignores_the_dark_appearance_cookie(): void
    {
        $response = $this->withUnencryptedCookie('appearance', 'dark')->get(route('order-lookup.create'));

        $response->assertOk();
        $response->assertDontSee('class="dark"', false);
    }

    public function test_admin_dashboard_still_respects_the_dark_appearance_cookie(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withUnencryptedCookie('appearance', 'dark')
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('class="dark"', false);
    }
}
