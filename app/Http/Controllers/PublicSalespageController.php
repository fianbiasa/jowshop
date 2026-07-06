<?php

namespace App\Http\Controllers;

use App\Concerns\SharesMetaPixelProp;
use App\Concerns\SharesSeoMeta;
use App\Enums\FunnelEventType;
use App\Enums\FunnelStatus;
use App\Models\Funnel;
use App\Services\FunnelTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicSalespageController extends Controller
{
    use SharesMetaPixelProp, SharesSeoMeta;

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

        $title = $funnel->salespage->seo_title ?: $funnel->salespage->title;
        $description = $funnel->salespage->seo_description
            ?: Str::limit(strip_tags($funnel->product->description ?? $funnel->salespage->title), 155);

        $this->shareSeoMeta(
            $title,
            $description,
            $funnel->product->thumbnail_path ? Storage::disk('public')->url($funnel->product->thumbnail_path) : null,
        );

        return Inertia::render('public/salespage-view', [
            'metaPixel' => $this->metaPixelProp($this->effectivePixelId($funnel), $viewEvent),
            'funnel' => [
                'name' => $funnel->name,
                'slug' => $funnel->slug,
            ],
            'salespage' => [
                'title' => $funnel->salespage->title,
                'content' => $funnel->salespage->content,
                'style' => $funnel->salespage->style,
                'seo_title' => $funnel->salespage->seo_title,
                'seo_description' => $description,
            ],
            'product' => [
                'name' => $funnel->product->name,
                'price' => $funnel->product->price,
            ],
        ]);
    }
}
