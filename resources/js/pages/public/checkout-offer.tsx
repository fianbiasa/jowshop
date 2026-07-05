import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import { trackMetaPixelEvent } from '@/components/meta-pixel';
import {
    CTA_BUTTON_CLASS,
    HEADLINE_CLASS,
    KICKER_CLASS,
    MAIN_CLASS,
    OFFER_CARD_CLASS,
    PAGE_BG_CLASS,
    SECONDARY_BUTTON_CLASS,
    SUBHEADLINE_CLASS,
} from '@/lib/salespage-themes';
import { respond } from '@/routes/public/checkout/offer';
import type { SalespageStyle as Style } from '@/types/models';

function formatPrice(price: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(price);
}

export default function CheckoutOffer({
    funnel,
    style,
    pixelId,
    offer,
}: {
    funnel: { name: string; slug: string };
    style: Style;
    pixelId: string | null;
    offer: {
        id: number;
        headline: string;
        description: string | null;
        product_name: string;
        price: number;
    };
}) {
    const [acceptEventId] = useState(() => crypto.randomUUID());

    return (
        <>
            <Head title={`Tawaran Spesial — ${funnel.name}`} />

            <div className={`min-h-screen ${PAGE_BG_CLASS[style]}`}>
                <main className={MAIN_CLASS[style]}>
                    <div>
                        <p className={KICKER_CLASS[style]}>Tunggu dulu!</p>
                        <h1 className={`mt-2 ${HEADLINE_CLASS[style]}`}>
                            {offer.headline}
                        </h1>
                        {offer.description && (
                            <p className={`mt-2 ${SUBHEADLINE_CLASS[style]}`}>
                                {offer.description}
                            </p>
                        )}
                    </div>

                    <div className={OFFER_CARD_CLASS[style]}>
                        {offer.product_name} — {formatPrice(offer.price)}
                    </div>

                    <div className="flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                        <Form
                            {...respond.form([funnel.slug, offer.id])}
                            className="contents"
                        >
                            {({ processing }) => (
                                <>
                                    <input
                                        type="hidden"
                                        name="response"
                                        value="accepted"
                                    />
                                    <input
                                        type="hidden"
                                        name="event_id"
                                        value={acceptEventId}
                                    />
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        onClick={() =>
                                            pixelId &&
                                            trackMetaPixelEvent(
                                                pixelId,
                                                'AddToCart',
                                                acceptEventId,
                                                {
                                                    value: offer.price,
                                                    currency: 'IDR',
                                                },
                                            )
                                        }
                                        className={`${CTA_BUTTON_CLASS[style]} disabled:opacity-50`}
                                    >
                                        Ya, Tambahkan!
                                    </button>
                                </>
                            )}
                        </Form>

                        <Form
                            {...respond.form([funnel.slug, offer.id])}
                            className="contents"
                        >
                            {({ processing }) => (
                                <>
                                    <input
                                        type="hidden"
                                        name="response"
                                        value="declined"
                                    />
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className={`${SECONDARY_BUTTON_CLASS[style]} disabled:opacity-50`}
                                    >
                                        Tidak, terima kasih
                                    </button>
                                </>
                            )}
                        </Form>
                    </div>
                </main>
            </div>
        </>
    );
}
