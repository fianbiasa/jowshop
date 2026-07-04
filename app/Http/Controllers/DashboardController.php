<?php

namespace App\Http\Controllers;

use App\Models\Funnel;
use App\Services\FunnelAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the funnel analytics dashboard, optionally filtered by funnel,
     * date range, and UTM source.
     */
    public function index(Request $request, FunnelAnalyticsService $analytics): Response
    {
        $funnelId = $request->integer('funnel_id') ?: null;
        $from = $request->filled('from') ? Date::parse($request->string('from')->toString())->startOfDay() : null;
        $to = $request->filled('to') ? Date::parse($request->string('to')->toString())->endOfDay() : null;
        $utmSource = $request->string('utm_source')->toString() ?: null;

        $summary = $analytics->summarize($funnelId, $from, $to, $utmSource);

        return Inertia::render('dashboard', [
            'summary' => $summary,
            'funnels' => Funnel::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'funnel_id' => $funnelId,
                'from' => $request->string('from')->toString() ?: null,
                'to' => $request->string('to')->toString() ?: null,
                'utm_source' => $utmSource,
            ],
        ]);
    }
}
