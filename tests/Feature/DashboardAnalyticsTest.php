<?php

namespace Tests\Feature;

use App\Enums\FunnelEventType;
use App\Enums\OrderItemType;
use App\Enums\OrderStatus;
use App\Models\Funnel;
use App\Models\FunnelOffer;
use App\Models\FunnelSession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_shows_visitor_and_funnel_step_counts(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->create();

        $sessionA = FunnelSession::factory()->for($funnel)->create();
        $sessionA->recordEvent(FunnelEventType::SalespageView);
        $sessionA->recordEvent(FunnelEventType::CheckoutView);
        $sessionA->recordEvent(FunnelEventType::PaymentSuccess);

        $sessionB = FunnelSession::factory()->for($funnel)->create();
        $sessionB->recordEvent(FunnelEventType::SalespageView);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('summary.visitor_count', 2)
            ->where('summary.funnel_steps.0.event', 'salespage_view')
            ->where('summary.funnel_steps.0.count', 2)
            ->where('summary.funnel_steps.1.event', 'checkout_view')
            ->where('summary.funnel_steps.1.count', 1)
            ->where('summary.funnel_steps.3.event', 'payment_success')
            ->where('summary.funnel_steps.3.count', 1)
        );
    }

    public function test_dashboard_scopes_counts_to_selected_funnel(): void
    {
        $user = User::factory()->create();
        $funnelA = Funnel::factory()->create();
        $funnelB = Funnel::factory()->create();

        FunnelSession::factory()->for($funnelA)->create()->recordEvent(FunnelEventType::SalespageView);
        FunnelSession::factory()->for($funnelB)->create()->recordEvent(FunnelEventType::SalespageView);
        FunnelSession::factory()->for($funnelB)->create()->recordEvent(FunnelEventType::SalespageView);

        $response = $this->actingAs($user)->get(route('dashboard', ['funnel_id' => $funnelB->id]));

        $response->assertInertia(fn ($page) => $page
            ->where('summary.visitor_count', 2)
        );
    }

    public function test_dashboard_filters_by_utm_source(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->create();

        $fbVisitor = Visitor::factory()->create(['utm_source' => 'facebook']);
        $googleVisitor = Visitor::factory()->create(['utm_source' => 'google']);

        FunnelSession::factory()->for($funnel)->for($fbVisitor)->create()->recordEvent(FunnelEventType::SalespageView);
        FunnelSession::factory()->for($funnel)->for($googleVisitor)->create()->recordEvent(FunnelEventType::SalespageView);

        $response = $this->actingAs($user)->get(route('dashboard', ['utm_source' => 'facebook']));

        $response->assertInertia(fn ($page) => $page
            ->where('summary.visitor_count', 1)
        );
    }

    public function test_dashboard_calculates_offer_take_rate(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->create();
        $bump = FunnelOffer::factory()->for($funnel)->bump()->create();

        $sessionA = FunnelSession::factory()->for($funnel)->create();
        $sessionA->recordEvent(FunnelEventType::BumpView, $bump);
        $sessionA->recordEvent(FunnelEventType::BumpAccepted, $bump);

        $sessionB = FunnelSession::factory()->for($funnel)->create();
        $sessionB->recordEvent(FunnelEventType::BumpView, $bump);
        $sessionB->recordEvent(FunnelEventType::BumpDeclined, $bump);

        $response = $this->actingAs($user)->get(route('dashboard', ['funnel_id' => $funnel->id]));

        $response->assertInertia(fn ($page) => $page
            ->where('summary.offers.0.offer_id', $bump->id)
            ->where('summary.offers.0.view_count', 2)
            ->where('summary.offers.0.accepted_count', 1)
            ->where('summary.offers.0.take_rate', 50)
        );
    }

    public function test_dashboard_revenue_only_counts_paid_orders(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->create();

        Order::factory()->for($funnel)->create(['status' => OrderStatus::Paid, 'total' => 45000]);
        Order::factory()->for($funnel)->create(['status' => OrderStatus::Completed, 'total' => 30000]);
        Order::factory()->for($funnel)->create(['status' => OrderStatus::Pending, 'total' => 99000]);
        Order::factory()->for($funnel)->create(['status' => OrderStatus::Cancelled, 'total' => 99000]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->where('summary.revenue', '75000.00')
            ->where('summary.order_count', 2)
        );
    }

    public function test_dashboard_revenue_breakdown_groups_by_offer_type(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->create();
        $order = Order::factory()->for($funnel)->create(['status' => OrderStatus::Paid]);

        OrderItem::factory()->for($order)->create([
            'offer_type' => OrderItemType::Main,
            'unit_price' => 45000,
            'quantity' => 1,
        ]);
        OrderItem::factory()->for($order)->create([
            'offer_type' => OrderItemType::Bump,
            'unit_price' => 5000,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->where('summary.revenue_breakdown.0.offer_type', 'main')
            ->where('summary.revenue_breakdown.0.revenue', '45000.00')
            ->where('summary.revenue_breakdown.1.offer_type', 'bump')
            ->where('summary.revenue_breakdown.1.revenue', '5000.00')
            ->where('summary.revenue_breakdown.2.revenue', '0.00')
        );
    }

    public function test_dashboard_groups_traffic_sources_by_utm_data(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->create();

        $facebookVisitor = Visitor::factory()->create(['utm_source' => 'facebook']);
        $googleAdsVisitor = Visitor::factory()->create(['utm_source' => 'google', 'utm_medium' => 'cpc']);

        FunnelSession::factory()->for($funnel)->for($facebookVisitor)->create();
        FunnelSession::factory()->for($funnel)->for($googleAdsVisitor)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->where('summary.traffic_sources.0.source', 'Facebook')
            ->where('summary.traffic_sources.0.visitor_count', 1)
            ->where('summary.traffic_sources.1.source', 'Google Ads')
            ->where('summary.traffic_sources.1.visitor_count', 1)
        );
    }

    public function test_dashboard_classifies_traffic_source_from_referrer_when_no_utm(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->create();

        $fbVisitor = Visitor::factory()->create(['referrer' => 'https://www.facebook.com/']);
        $directVisitor = Visitor::factory()->create(['referrer' => null]);
        $sameSiteVisitor = Visitor::factory()->create(['referrer' => 'https://jowshop.web.id/']);

        FunnelSession::factory()->for($funnel)->for($fbVisitor)->create();
        FunnelSession::factory()->for($funnel)->for($directVisitor)->create();
        FunnelSession::factory()->for($funnel)->for($sameSiteVisitor)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->where('summary.traffic_sources.0.source', 'Direct')
            ->where('summary.traffic_sources.0.visitor_count', 2)
            ->where('summary.traffic_sources.1.source', 'Facebook (Organik)')
            ->where('summary.traffic_sources.1.visitor_count', 1)
        );
    }

    public function test_dashboard_traffic_source_tracks_conversions_and_revenue(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->create();

        $visitor = Visitor::factory()->create(['utm_source' => 'facebook']);
        FunnelSession::factory()->for($funnel)->for($visitor)->create();

        Order::factory()->for($funnel)->create([
            'visitor_id' => $visitor->id,
            'status' => OrderStatus::Paid,
            'total' => 50000,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->where('summary.traffic_sources.0.source', 'Facebook')
            ->where('summary.traffic_sources.0.visitor_count', 1)
            ->where('summary.traffic_sources.0.order_count', 1)
            ->where('summary.traffic_sources.0.revenue', '50000.00')
            ->where('summary.traffic_sources.0.conversion_rate', 100)
        );
    }

    public function test_dashboard_ranks_pages_by_view_count(): void
    {
        $user = User::factory()->create();
        $popularFunnel = Funnel::factory()->create(['name' => 'Kelas Online']);
        $quietFunnel = Funnel::factory()->create(['name' => 'Ebook Diet']);

        FunnelSession::factory()->for($popularFunnel)->create()->recordEvent(FunnelEventType::SalespageView);
        FunnelSession::factory()->for($popularFunnel)->create()->recordEvent(FunnelEventType::SalespageView);
        FunnelSession::factory()->for($quietFunnel)->create()->recordEvent(FunnelEventType::SalespageView);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->where('summary.page_views.0.funnel_id', $popularFunnel->id)
            ->where('summary.page_views.0.name', 'Kelas Online')
            ->where('summary.page_views.0.view_count', 2)
            ->where('summary.page_views.1.funnel_id', $quietFunnel->id)
            ->where('summary.page_views.1.view_count', 1)
        );
    }

    public function test_dashboard_ranks_best_selling_products_by_quantity_sold(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->create();
        $bestseller = Product::factory()->create(['name' => 'Kopi Robusta']);
        $slowMover = Product::factory()->create(['name' => 'Teh Hijau']);

        $orderA = Order::factory()->for($funnel)->create(['status' => OrderStatus::Paid]);
        OrderItem::factory()->for($orderA)->for($bestseller)->create(['quantity' => 3, 'unit_price' => 20000]);

        $orderB = Order::factory()->for($funnel)->create(['status' => OrderStatus::Completed]);
        OrderItem::factory()->for($orderB)->for($bestseller)->create(['quantity' => 2, 'unit_price' => 20000]);
        OrderItem::factory()->for($orderB)->for($slowMover)->create(['quantity' => 1, 'unit_price' => 15000]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->where('summary.best_selling_products.0.product_id', $bestseller->id)
            ->where('summary.best_selling_products.0.name', 'Kopi Robusta')
            ->where('summary.best_selling_products.0.quantity_sold', 5)
            ->where('summary.best_selling_products.0.revenue', '100000.00')
            ->where('summary.best_selling_products.1.product_id', $slowMover->id)
            ->where('summary.best_selling_products.1.quantity_sold', 1)
            ->where('summary.best_selling_products.1.revenue', '15000.00')
        );
    }
}
