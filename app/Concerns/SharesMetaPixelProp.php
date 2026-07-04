<?php

namespace App\Concerns;

use App\Models\FunnelEvent;
use App\Models\Order;

trait SharesMetaPixelProp
{
    /**
     * Build the prop the frontend needs to fire the matching client-side
     * Facebook Pixel event (same `event_id` as the server-side CAPI send,
     * for Meta's official Pixel/CAPI deduplication).
     *
     * @return array{pixel_id: string, event_name: string, event_id: string, value?: float, currency?: string}|null
     */
    private function metaPixelProp(?string $pixelId, ?FunnelEvent $event, ?Order $order = null): ?array
    {
        if ($pixelId === null || $event === null) {
            return null;
        }

        $eventName = $event->event_type->toMetaStandardEvent();

        if ($eventName === null) {
            return null;
        }

        $prop = [
            'pixel_id' => $pixelId,
            'event_name' => $eventName,
            'event_id' => $event->external_event_id,
        ];

        if ($eventName === 'Purchase' && $order !== null) {
            $prop['value'] = (float) $order->total;
            $prop['currency'] = 'IDR';
        }

        return $prop;
    }
}
