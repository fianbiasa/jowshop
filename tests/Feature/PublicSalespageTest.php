<?php

namespace Tests\Feature;

use App\Enums\FunnelStatus;
use App\Models\Funnel;
use App\Models\Salespage;
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
            ->component('public/salespage')
            ->where('salespage.title', 'Kopi Robusta Terbaik')
        );
    }

    public function test_unknown_slug_returns_404(): void
    {
        $response = $this->get('/f/tidak-ada');

        $response->assertNotFound();
    }
}
