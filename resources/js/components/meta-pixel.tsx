import { useEffect } from 'react';
import { loadPixelScript } from '@/lib/meta-pixel';
import type { MetaPixelEvent } from '@/lib/meta-pixel';

/**
 * Fires the browser-side Facebook Pixel event that matches a server-side
 * Conversions API send, using the same `event_id` for Meta's official
 * Pixel/CAPI deduplication.
 */
export default function MetaPixel({ event }: { event: MetaPixelEvent | null }) {
    useEffect(() => {
        if (!event) {
            return;
        }

        loadPixelScript(event.pixel_id);

        const customData =
            event.value !== undefined
                ? { value: event.value, currency: event.currency }
                : undefined;

        window.fbq?.('track', event.event_name, customData, {
            eventID: event.event_id,
        });
    }, [event]);

    return null;
}
