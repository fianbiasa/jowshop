<?php

namespace Tests\Feature;

use App\Enums\FunnelEventType;
use App\Models\Funnel;
use App\Models\FunnelSession;
use App\Models\Salespage;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_visitor_is_created_on_first_salespage_view(): void
    {
        $funnel = Funnel::factory()->published()->create(['slug' => 'kopi']);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $this->get('/f/kopi?utm_source=facebook&utm_campaign=promo1');

        $this->assertSame(1, Visitor::query()->count());

        $visitor = Visitor::first();
        $this->assertSame('facebook', $visitor->utm_source);
        $this->assertSame('promo1', $visitor->utm_campaign);
        $this->assertNotNull($visitor->first_seen_at);
    }

    public function test_returning_visitor_with_same_cookie_is_recognized(): void
    {
        $funnel = Funnel::factory()->published()->create(['slug' => 'kopi']);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $this->get('/f/kopi');
        $this->assertSame(1, Visitor::query()->count());
        $visitor = Visitor::query()->firstOrFail();

        $this->withCookie('visitor_uuid', $visitor->uuid)->get('/f/kopi');

        $this->assertSame(1, Visitor::query()->count());
    }

    public function test_salespage_view_is_recorded_once_per_funnel_session(): void
    {
        $funnel = Funnel::factory()->published()->create(['slug' => 'kopi']);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $this->get('/f/kopi');
        $this->get('/f/kopi');

        $session = FunnelSession::query()->firstOrFail();
        $this->assertSame(
            1,
            $session->events()->where('event_type', FunnelEventType::SalespageView)->count()
        );
    }

    public function test_funnel_event_has_a_unique_external_event_id(): void
    {
        $funnel = Funnel::factory()->published()->create(['slug' => 'kopi']);
        Salespage::factory()->published()->create(['funnel_id' => $funnel->id]);

        $this->get('/f/kopi');

        $event = FunnelSession::query()->firstOrFail()->events()->firstOrFail();
        $this->assertNotEmpty($event->external_event_id);
    }
}
