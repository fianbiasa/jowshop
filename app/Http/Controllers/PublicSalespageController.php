<?php

namespace App\Http\Controllers;

use App\Concerns\SharesMetaPixelProp;
use App\Enums\FunnelEventType;
use App\Enums\FunnelStatus;
use App\Models\Funnel;
use App\Services\FunnelTracker;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicSalespageController extends Controller
{
    use SharesMetaPixelProp;

    /**
     * Display the public salespage for a published funnel.
     */
    public function show(Request $request, Funnel $funnel, FunnelTracker $tracker): Response
    {
        $funnel->load(['salespage', 'product']);

        if ($funnel->status !== FunnelStatus::Published || $funnel->salespage?->isPublished() !== true) {
            throw new NotFoundHttpException;
        }

        $session = $tracker->resolveSession($request, $funnel);
        $tracker->recordOnce($session, FunnelEventType::SalespageView);
        $viewEvent = $session->events()->where('event_type', FunnelEventType::SalespageView)->first();

        return Inertia::render('public/salespage', [
            'metaPixel' => $this->metaPixelProp($funnel->fbPixelId(), $viewEvent),
            'funnel' => [
                'name' => $funnel->name,
                'slug' => $funnel->slug,
            ],
            'salespage' => [
                'title' => $funnel->salespage->title,
                'content' => $funnel->salespage->content,
                'seo_title' => $funnel->salespage->seo_title,
                'seo_description' => $funnel->salespage->seo_description,
            ],
            'product' => [
                'name' => $funnel->product->name,
                'price' => $funnel->product->price,
            ],
        ]);
    }
}
