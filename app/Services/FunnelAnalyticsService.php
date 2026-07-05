<?php

namespace App\Services;

use App\Enums\FunnelEventType;
use App\Enums\OfferStage;
use App\Enums\OrderItemType;
use App\Enums\OrderStatus;
use App\Models\Funnel;
use App\Models\FunnelEvent;
use App\Models\FunnelOffer;
use App\Models\FunnelSession;
use App\Models\Order;
use App\Models\Product;
use App\Models\Visitor;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FunnelAnalyticsService
{
    /**
     * The main step-by-step conversion funnel, in order, and the label
     * shown for each step on the dashboard.
     *
     * @var array<string, string>
     */
    private const STEPS = [
        FunnelEventType::SalespageView->value => 'Lihat Salespage',
        FunnelEventType::CheckoutView->value => 'Buka Checkout',
        FunnelEventType::CheckoutSubmitted->value => 'Submit Data Pembeli',
        FunnelEventType::PaymentSuccess->value => 'Bayar Sukses',
    ];

    /**
     * Order statuses that represent a completed, revenue-generating sale.
     *
     * @var array<int, OrderStatus>
     */
    private const REVENUE_STATUSES = [
        OrderStatus::Paid,
        OrderStatus::Processing,
        OrderStatus::Shipped,
        OrderStatus::Completed,
    ];

    /**
     * @return array{
     *     visitor_count: int,
     *     order_count: int,
     *     revenue: string,
     *     funnel_steps: array<int, array{event: string, label: string, count: int, rate: float}>,
     *     offers: array<int, array{offer_id: int, headline: string, stage: string, view_count: int, accepted_count: int, take_rate: float}>,
     *     revenue_breakdown: array<int, array{offer_type: string, revenue: string}>,
     *     traffic_sources: array<int, array{source: string, visitor_count: int, order_count: int, revenue: string, conversion_rate: float}>,
     *     page_views: array<int, array{funnel_id: int, name: string, slug: string, view_count: int}>,
     *     best_selling_products: array<int, array{product_id: int, name: string, quantity_sold: int, revenue: string}>,
     * }
     */
    public function summarize(?int $funnelId, ?CarbonInterface $from, ?CarbonInterface $to, ?string $utmSource): array
    {
        $sessionIds = FunnelSession::query()
            ->when($funnelId, fn ($query) => $query->where('funnel_id', $funnelId))
            ->when($from, fn ($query) => $query->where('started_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('started_at', '<=', $to))
            ->when($utmSource, fn ($query) => $query->whereHas(
                'visitor',
                fn ($visitorQuery) => $visitorQuery->where('utm_source', $utmSource),
            ))
            ->pluck('id');

        $visitorCount = FunnelSession::query()
            ->whereIn('id', $sessionIds)
            ->distinct('visitor_id')
            ->count('visitor_id');

        $orderQuery = Order::query()
            ->whereIn('status', self::REVENUE_STATUSES)
            ->when($funnelId, fn ($query) => $query->where('funnel_id', $funnelId))
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to));

        $revenue = number_format((float) (clone $orderQuery)->sum('total'), 2, '.', '');
        $orderCount = (clone $orderQuery)->count();

        return [
            'visitor_count' => $visitorCount,
            'order_count' => $orderCount,
            'revenue' => $revenue,
            'funnel_steps' => $this->funnelSteps($sessionIds),
            'offers' => $this->offerTakeRates($funnelId, $sessionIds),
            'revenue_breakdown' => $this->revenueBreakdown($funnelId, $from, $to),
            'traffic_sources' => $this->trafficSources($sessionIds, $funnelId, $from, $to),
            'page_views' => $this->pageViews($sessionIds),
            'best_selling_products' => $this->bestSellingProducts($funnelId, $from, $to),
        ];
    }

    /**
     * @return array<int, array{offer_type: string, revenue: string}>
     */
    private function revenueBreakdown(?int $funnelId, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $totals = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', array_map(fn (OrderStatus $status) => $status->value, self::REVENUE_STATUSES))
            ->when($funnelId, fn ($query) => $query->where('orders.funnel_id', $funnelId))
            ->when($from, fn ($query) => $query->where('orders.created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('orders.created_at', '<=', $to))
            ->selectRaw('order_items.offer_type, sum(order_items.unit_price * order_items.quantity) as total')
            ->groupBy('order_items.offer_type')
            ->pluck('total', 'order_items.offer_type');

        return array_map(
            fn (OrderItemType $type) => [
                'offer_type' => $type->value,
                'revenue' => number_format((float) ($totals[$type->value] ?? 0), 2, '.', ''),
            ],
            OrderItemType::cases(),
        );
    }

    /**
     * Which funnel's salespage got the most views, most-viewed first.
     *
     * @param  Collection<int, int>  $sessionIds
     * @return array<int, array{funnel_id: int, name: string, slug: string, view_count: int}>
     */
    private function pageViews($sessionIds): array
    {
        $counts = FunnelEvent::query()
            ->toBase()
            ->join('funnel_sessions', 'funnel_sessions.id', '=', 'funnel_events.funnel_session_id')
            ->whereIn('funnel_events.funnel_session_id', $sessionIds)
            ->where('funnel_events.event_type', FunnelEventType::SalespageView->value)
            ->selectRaw('funnel_sessions.funnel_id, count(*) as total')
            ->groupBy('funnel_sessions.funnel_id')
            ->pluck('total', 'funnel_sessions.funnel_id');

        return Funnel::query()
            ->whereIn('id', $counts->keys())
            ->get(['id', 'name', 'slug'])
            ->map(fn (Funnel $funnel) => [
                'funnel_id' => $funnel->id,
                'name' => $funnel->name,
                'slug' => $funnel->slug,
                'view_count' => (int) $counts[$funnel->id],
            ])
            ->sortByDesc('view_count')
            ->values()
            ->all();
    }

    /**
     * Best-selling products across all offer positions (main/bump/upsell/
     * downsell combined), most units sold first.
     *
     * @return array<int, array{product_id: int, name: string, quantity_sold: int, revenue: string}>
     */
    private function bestSellingProducts(?int $funnelId, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $totals = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', array_map(fn (OrderStatus $status) => $status->value, self::REVENUE_STATUSES))
            ->when($funnelId, fn ($query) => $query->where('orders.funnel_id', $funnelId))
            ->when($from, fn ($query) => $query->where('orders.created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('orders.created_at', '<=', $to))
            ->selectRaw('order_items.product_id, sum(order_items.quantity) as quantity_sold, sum(order_items.unit_price * order_items.quantity) as revenue')
            ->groupBy('order_items.product_id')
            ->orderByDesc('quantity_sold')
            ->get();

        $products = Product::query()
            ->whereIn('id', $totals->pluck('product_id'))
            ->get(['id', 'name'])
            ->keyBy('id');

        return $totals->map(fn ($row) => [
            'product_id' => (int) $row->product_id,
            'name' => $products[$row->product_id]->name,
            'quantity_sold' => (int) $row->quantity_sold,
            'revenue' => number_format((float) $row->revenue, 2, '.', ''),
        ])->all();
    }

    /**
     * Where visitors came from: paid ads (Google/Facebook/...) when UTM tags
     * are present, otherwise a best-effort guess from the referrer, falling
     * back to "Direct" for no referrer or a same-site referrer.
     *
     * @param  Collection<int, int>  $sessionIds
     * @return array<int, array{source: string, visitor_count: int, order_count: int, revenue: string, conversion_rate: float}>
     */
    private function trafficSources($sessionIds, ?int $funnelId, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $visitorIds = FunnelSession::query()
            ->whereIn('id', $sessionIds)
            ->distinct()
            ->pluck('visitor_id');

        $visitors = Visitor::query()
            ->whereIn('id', $visitorIds)
            ->orderBy('id')
            ->get(['id', 'utm_source', 'utm_medium', 'referrer']);

        $orderTotals = Order::query()
            ->whereIn('status', array_map(fn (OrderStatus $status) => $status->value, self::REVENUE_STATUSES))
            ->whereIn('visitor_id', $visitorIds)
            ->when($funnelId, fn ($query) => $query->where('funnel_id', $funnelId))
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->selectRaw('visitor_id, count(*) as orders, sum(total) as revenue')
            ->groupBy('visitor_id')
            ->get()
            ->keyBy('visitor_id');

        $grouped = [];

        foreach ($visitors as $visitor) {
            $label = $this->classifyTrafficSource($visitor->utm_source, $visitor->utm_medium, $visitor->referrer);

            $grouped[$label] ??= ['visitor_count' => 0, 'converted_visitor_count' => 0, 'order_count' => 0, 'revenue' => 0.0];
            $grouped[$label]['visitor_count']++;

            $orderRow = $orderTotals->get($visitor->id);

            if ($orderRow) {
                $grouped[$label]['converted_visitor_count']++;
                $grouped[$label]['order_count'] += (int) $orderRow->orders;
                $grouped[$label]['revenue'] += (float) $orderRow->revenue;
            }
        }

        $result = [];

        foreach ($grouped as $source => $data) {
            $result[] = [
                'source' => $source,
                'visitor_count' => $data['visitor_count'],
                'order_count' => $data['order_count'],
                'revenue' => number_format($data['revenue'], 2, '.', ''),
                'conversion_rate' => $data['visitor_count'] > 0
                    ? round(($data['converted_visitor_count'] / $data['visitor_count']) * 100, 1)
                    : 0.0,
            ];
        }

        usort($result, fn (array $a, array $b) => $b['visitor_count'] <=> $a['visitor_count']);

        return $result;
    }

    /**
     * Normalize a visitor's UTM/referrer data into a human-readable traffic
     * source label. UTM tags (set by ad platforms/marketers) take priority
     * over the referrer, since a referrer alone can't distinguish a paid
     * click from an organic one.
     */
    private function classifyTrafficSource(?string $utmSource, ?string $utmMedium, ?string $referrer): string
    {
        if ($utmSource) {
            $normalized = strtolower($utmSource);
            $isPaid = $utmMedium && in_array(strtolower($utmMedium), ['cpc', 'ppc', 'paid', 'paid_social', 'ads'], true);

            $label = match (true) {
                in_array($normalized, ['google', 'adwords', 'gads'], true) => 'Google',
                in_array($normalized, ['fb', 'facebook', 'meta'], true) => 'Facebook',
                in_array($normalized, ['ig', 'instagram'], true) => 'Instagram',
                $normalized === 'tiktok' => 'TikTok',
                $normalized === 'whatsapp' => 'WhatsApp',
                $normalized === 'youtube' => 'YouTube',
                default => ucfirst($utmSource),
            };

            return $isPaid ? "{$label} Ads" : $label;
        }

        if ($referrer) {
            $host = parse_url($referrer, PHP_URL_HOST) ?: $referrer;
            $host = preg_replace('/^www\./', '', strtolower((string) $host));
            $appHost = preg_replace('/^www\./', '', strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)));

            if ($host === $appHost) {
                return 'Direct';
            }

            return match (true) {
                str_contains($host, 'google.') => 'Google (Organik)',
                str_contains($host, 'facebook.com') || str_contains($host, 'fb.com') => 'Facebook (Organik)',
                str_contains($host, 'instagram.com') => 'Instagram (Organik)',
                str_contains($host, 'tiktok.com') => 'TikTok (Organik)',
                str_contains($host, 'youtube.com') => 'YouTube (Organik)',
                str_contains($host, 'whatsapp.com') || str_contains($host, 'wa.me') => 'WhatsApp',
                default => "Referral: {$host}",
            };
        }

        return 'Direct';
    }

    /**
     * @param  Collection<int, int>  $sessionIds
     * @return array<int, array{event: string, label: string, count: int, rate: float}>
     */
    private function funnelSteps($sessionIds): array
    {
        $counts = FunnelEvent::query()
            ->toBase()
            ->whereIn('funnel_session_id', $sessionIds)
            ->whereIn('event_type', array_keys(self::STEPS))
            ->selectRaw('event_type, count(distinct funnel_session_id) as total')
            ->groupBy('event_type')
            ->pluck('total', 'event_type');

        $firstStepCount = (int) ($counts[array_key_first(self::STEPS)] ?? 0);

        $steps = [];

        foreach (self::STEPS as $type => $label) {
            $count = (int) ($counts[$type] ?? 0);

            $steps[] = [
                'event' => $type,
                'label' => $label,
                'count' => $count,
                'rate' => $firstStepCount > 0 ? round(($count / $firstStepCount) * 100, 1) : 0.0,
            ];
        }

        return $steps;
    }

    /**
     * @param  Collection<int, int>  $sessionIds
     * @return array<int, array{offer_id: int, headline: string, stage: string, view_count: int, accepted_count: int, take_rate: float}>
     */
    private function offerTakeRates(?int $funnelId, $sessionIds): array
    {
        $viewEventFor = [
            OfferStage::Bump->value => FunnelEventType::BumpView->value,
            OfferStage::Upsell->value => FunnelEventType::UpsellView->value,
            OfferStage::Downsell->value => FunnelEventType::DownsellView->value,
        ];

        $acceptedEventFor = [
            OfferStage::Bump->value => FunnelEventType::BumpAccepted->value,
            OfferStage::Upsell->value => FunnelEventType::UpsellAccepted->value,
            OfferStage::Downsell->value => FunnelEventType::DownsellAccepted->value,
        ];

        $offers = FunnelOffer::query()
            ->when($funnelId, fn ($query) => $query->where('funnel_id', $funnelId))
            ->orderBy('sequence')
            ->get();

        return $offers->map(function (FunnelOffer $offer) use ($sessionIds, $viewEventFor, $acceptedEventFor) {
            $viewCount = FunnelEvent::query()
                ->whereIn('funnel_session_id', $sessionIds)
                ->where('funnel_offer_id', $offer->id)
                ->where('event_type', $viewEventFor[$offer->stage->value])
                ->count();

            $acceptedCount = FunnelEvent::query()
                ->whereIn('funnel_session_id', $sessionIds)
                ->where('funnel_offer_id', $offer->id)
                ->where('event_type', $acceptedEventFor[$offer->stage->value])
                ->count();

            return [
                'offer_id' => $offer->id,
                'headline' => $offer->headline,
                'stage' => $offer->stage->value,
                'view_count' => $viewCount,
                'accepted_count' => $acceptedCount,
                'take_rate' => $viewCount > 0 ? round(($acceptedCount / $viewCount) * 100, 1) : 0.0,
            ];
        })->all();
    }
}
