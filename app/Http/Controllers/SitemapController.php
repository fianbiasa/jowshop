<?php

namespace App\Http\Controllers;

use App\Enums\FunnelStatus;
use App\Models\Funnel;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * List every published funnel's salespage for search engine discovery.
     */
    public function index(): Response
    {
        $funnels = Funnel::query()
            ->where('status', FunnelStatus::Published)
            ->whereHas('salespage', fn ($query) => $query->whereNotNull('published_at'))
            ->with('salespage')
            ->get();

        return response()
            ->view('sitemap', ['funnels' => $funnels])
            ->header('Content-Type', 'text/xml');
    }
}
