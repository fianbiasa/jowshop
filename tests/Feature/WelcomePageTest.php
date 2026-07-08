<?php

namespace Tests\Feature;

use App\Models\Funnel;
use App\Models\Salespage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_published_funnels_with_a_published_salespage_are_listed(): void
    {
        $visible = Funnel::factory()->published()->create(['name' => 'Kopi Robusta']);
        Salespage::factory()->published()->create(['funnel_id' => $visible->id]);

        $draftFunnel = Funnel::factory()->create(['name' => 'Draft Funnel']);
        Salespage::factory()->published()->create(['funnel_id' => $draftFunnel->id]);

        $unpublishedSalespage = Funnel::factory()->published()->create(['name' => 'Belum Ada Salespage']);
        Salespage::factory()->create(['funnel_id' => $unpublishedSalespage->id]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('welcome')
            ->has('funnels', 1)
            ->where('funnels.0.name', 'Kopi Robusta')
            ->where('funnels.0.slug', $visible->slug)
        );
    }

    public function test_guests_see_auth_check_as_false(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('auth.user', null)
            ->where('auth.check', false)
        );
    }

    public function test_logged_in_users_see_auth_check_as_true_without_leaking_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('auth.user', null)
            ->where('auth.check', true)
        );
    }
}
