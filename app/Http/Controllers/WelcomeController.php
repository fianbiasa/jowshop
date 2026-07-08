<?php

namespace App\Http\Controllers;

use App\Enums\FunnelStatus;
use App\Models\Funnel;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    /**
     * Show the public homepage: a short site intro plus a catalog of every
     * published funnel, since this install is a single business's
     * storefront rather than a multi-tenant SaaS product.
     */
    public function index(): Response
    {
        $funnels = Funnel::query()
            ->where('status', FunnelStatus::Published)
            ->whereHas('salespage', fn ($query) => $query->whereNotNull('published_at'))
            ->with('product')
            ->latest()
            ->get();

        return Inertia::render('welcome', [
            'funnels' => $funnels->map(fn (Funnel $funnel) => [
                'name' => $funnel->name,
                'slug' => $funnel->slug,
                'price' => $funnel->product->price,
                'thumbnailUrl' => $funnel->product->thumbnail_url,
            ]),
        ]);
    }
}
