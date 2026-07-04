<?php

namespace App\Jobs;

use App\Enums\MetaCapiEventStatus;
use App\Models\FunnelEvent;
use App\Models\MetaCapiEventLog;
use App\Models\MetaCapiSetting;
use App\Services\MetaConversionsApiClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMetaConversionEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $funnelEventId) {}

    /**
     * Send this funnel event to Meta's Conversions API, if it maps to a
     * standard event and CAPI is configured/active. No-ops otherwise —
     * this job is dispatched for every tracked funnel event, and most of
     * them aren't ad-relevant.
     */
    public function handle(MetaConversionsApiClient $client): void
    {
        $settings = MetaCapiSetting::query()->where('is_active', true)->first();

        if ($settings === null) {
            return;
        }

        $event = FunnelEvent::query()
            ->with(['funnelSession.visitor', 'funnelSession.funnel', 'funnelSession.order.customer'])
            ->find($this->funnelEventId);

        if ($event === null) {
            return;
        }

        $eventName = $event->event_type->toMetaStandardEvent();

        if ($eventName === null) {
            return;
        }

        $session = $event->funnelSession;
        $visitor = $session->visitor;
        $order = $session->order;
        $customer = $order?->customer;

        $userData = array_filter([
            'em' => $customer !== null ? [hash('sha256', strtolower(trim($customer->email)))] : null,
            'ph' => filled($customer?->phone) ? [hash('sha256', (string) preg_replace('/\D/', '', $customer->phone))] : null,
            'client_ip_address' => $visitor->ip_address,
            'client_user_agent' => $visitor->user_agent,
            'fbp' => $visitor->fbp,
            'fbc' => $visitor->fbc,
        ], fn (mixed $value) => $value !== null);

        $customData = [];

        if ($eventName === 'Purchase' && $order !== null) {
            $customData = ['value' => (float) $order->total, 'currency' => 'IDR'];
        }

        $pixelId = $session->funnel->fbPixelId() ?? $settings->pixel_id;

        $log = MetaCapiEventLog::query()->firstOrCreate(
            ['funnel_event_id' => $event->id],
            ['event_name' => $eventName, 'status' => MetaCapiEventStatus::Pending, 'attempts' => 0],
        );

        $result = $client->send($settings, $pixelId, $eventName, $event->external_event_id, $userData, $customData);

        $log->update([
            'status' => $result['successful'] ? MetaCapiEventStatus::Sent : MetaCapiEventStatus::Failed,
            'response_code' => $result['status'],
            'response_body' => $result['body'],
            'attempts' => $log->attempts + 1,
        ]);

        // Release (rather than throw) on failure: a bounded number of
        // retries via the queue's own backoff, without ever letting a flaky
        // Meta API response surface as an unhandled exception back through
        // whatever request triggered this event.
        if (! $result['successful'] && $log->attempts < $this->tries) {
            $this->release(30);
        }
    }
}
