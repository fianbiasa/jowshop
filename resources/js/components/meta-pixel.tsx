import { useEffect } from 'react';

type Fbq = {
    (...args: unknown[]): void;
    callMethod?: (...args: unknown[]) => void;
    queue: unknown[];
    push: Fbq;
    loaded: boolean;
    version: string;
};

declare global {
    interface Window {
        fbq?: Fbq;
        _fbq?: Fbq;
    }
}

export type MetaPixelEvent = {
    pixel_id: string;
    event_name: string;
    event_id: string;
    value?: number;
    currency?: string;
};

const initializedPixelIds = new Set<string>();

function loadPixelScript(pixelId: string) {
    if (!window.fbq) {
        const fbq = function (...args: unknown[]) {
            if (fbq.callMethod) {
                fbq.callMethod(...args);
            } else {
                fbq.queue.push(args);
            }
        } as Fbq;

        fbq.queue = [];
        fbq.loaded = true;
        fbq.version = '2.0';
        fbq.push = fbq;
        window.fbq = fbq;
        window._fbq ??= fbq;

        const script = document.createElement('script');
        script.async = true;
        script.src = 'https://connect.facebook.net/en_US/fbevents.js';
        document.head.appendChild(script);
    }

    // Inertia is an SPA — navigating between pages remounts this component
    // without a full page reload, so guard against re-`init`-ing the same
    // pixel ID on every navigation (fbq warns loudly about this).
    if (!initializedPixelIds.has(pixelId)) {
        window.fbq('init', pixelId);
        initializedPixelIds.add(pixelId);
    }
}

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
