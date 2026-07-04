<?php

namespace App\Services;

use App\Enums\FunnelEventType;
use App\Enums\OfferStage;
use App\Enums\OrderItemType;
use App\Enums\OrderStatus;
use App\Models\FunnelEvent;
use App\Models\FunnelOffer;
use App\Models\FunnelSession;
use App\Models\Order;
use Carbon\CarbonInterface;
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
     * @param  \Illuminate\Support\Collection<int, int>  $sessionIds
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
     * @param  \Illuminate\Support\Collection<int, int>  $sessionIds
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
