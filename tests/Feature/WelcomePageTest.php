<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

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
