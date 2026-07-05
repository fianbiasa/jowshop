<?php

namespace App\Services;

use App\Enums\FunnelEventType;
use App\Enums\FunnelSessionStatus;
use App\Jobs\SendMetaConversionEvent;
use App\Models\Funnel;
use App\Models\FunnelEvent;
use App\Models\FunnelOffer;
use App\Models\FunnelSession;
use Illuminate\Http\Request;

class FunnelTracker
{
    public function __construct(private readonly VisitorIdentifier $visitorIdentifier) {}

    /**
     * Resolve the active funnel session for the current visitor/browser
     * session, creating one if this is a new visit to this funnel.
     */
    public function resolveSession(Request $request, Funnel $funnel): FunnelSession
    {
        $visitor = $this->visitorIdentifier->identify($request);

        $sessionKey = "funnel_session.{$funnel->id}";
        $sessionId = $request->session()->get($sessionKey);

        if (is_int($sessionId)) {
            // Reuse the session regardless of status: even after it converts
            // (payment success), we still want subsequent requests in the
            // same browser journey — the thank-you page, upsell/downsell
            // chain — to keep tracking against this same session.
            $session = FunnelSession::query()->where('id', $sessionId)->first();

            if ($session !== null) {
                return $session;
            }
        }

        $session = FunnelSession::query()->create([
            'visitor_id' => $visitor->id,
            'funnel_id' => $funnel->id,
            'status' => FunnelSessionStatus::Active,
            'started_at' => now(),
        ]);

        $request->session()->put($sessionKey, $session->id);

        return $session;
    }

    /**
     * Record a funnel event, skipping if this (type, offer) combination was
     * already recorded for this session — keeps repeated page views/redirects
     * from producing noisy duplicate events.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function recordOnce(FunnelSession $session, FunnelEventType $type, ?FunnelOffer $offer = null, array $metadata = [], ?string $externalEventId = null): ?FunnelEvent
    {
        if ($session->hasEvent($type, $offer)) {
            return null;
        }

        $event = $session->recordEvent($type, $offer, $metadata, $externalEventId);

        SendMetaConversionEvent::dispatch($event->id)->afterResponse();

        return $event;
    }
}
