<?php

namespace App\Concerns;

use App\Models\Funnel;
use App\Models\FunnelEvent;
use App\Models\MetaCapiSetting;
use App\Models\Order;

trait SharesMetaPixelProp
{
    /**
     * A funnel's own pixel ID always wins when set; otherwise this falls
     * back to the global Meta CAPI settings' pixel ID — mirroring the same
     * fallback already used server-side for the Conversions API send (see
     * SendMetaConversionEvent::handle()), so the client-side browser pixel
     * and the server-side CAPI event report to the same pixel by default.
     */
    private function effectivePixelId(Funnel $funnel): ?string
    {
        return $funnel->fbPixelId() ?? MetaCapiSetting::query()->where('is_active', true)->value('pixel_id');
    }

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
