<?php

namespace Tests\Feature;

use App\Enums\FunnelStatus;
use App\Models\Funnel;
use App\Models\Salespage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_funnel_appears_in_the_sitemap(): void
    {
        $funnel = Funnel::factory()->published()->create(['slug' => 'kopi-robusta']);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
        $response->assertSee(route('public.salespage.show', $funnel), false);
    }

    public function test_draft_funnel_does_not_appear_in_the_sitemap(): void
    {
        $funnel = Funnel::factory()->create(['slug' => 'draft-funnel', 'status' => FunnelStatus::Draft]);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertDontSee(route('public.salespage.show', $funnel), false);
    }

    public function test_published_funnel_with_unpublished_salespage_does_not_appear_in_the_sitemap(): void
    {
        $funnel = Funnel::factory()->published()->create(['slug' => 'kopi-robusta']);
        Salespage::factory()->create(['funnel_id' => $funnel->id]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertDontSee(route('public.salespage.show', $funnel), false);
    }
}
