<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_page_is_displayed(): void
    {
        $response = $this->get(route('legal.terms'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('legal/terms'));
    }

    public function test_privacy_page_is_displayed(): void
    {
        $response = $this->get(route('legal.privacy'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('legal/privacy'));
    }

    public function test_contact_page_is_displayed_with_the_configured_contact_email(): void
    {
        $response = $this->get(route('legal.contact'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('legal/contact')
            ->where('branding.email', config('mail.from.address'))
        );
    }
}
