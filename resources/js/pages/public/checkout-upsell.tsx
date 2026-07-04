import { Form, Head } from '@inertiajs/react';
import { respond } from '@/routes/public/checkout/upsell';

function formatPrice(price: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(price);
}

export default function CheckoutUpsell({
    funnel,
    offer,
}: {
    funnel: { name: string; slug: string };
    order: { order_number: string };
    offer: {
        id: number;
        stage: 'upsell' | 'downsell';
        headline: string;
        description: string | null;
        product_name: string;
        price: number;
    };
}) {
    const eyebrow =
        offer.stage === 'upsell'
            ? 'Pesananmu berhasil! Sebelum lanjut...'
            : 'Bagaimana kalau ini saja?';

    return (
        <>
            <Head title={`Tawaran Spesial — ${funnel.name}`} />

            <main className="mx-auto max-w-xl space-y-8 px-4 py-12 text-center">
                <div>
                    <p className="text-sm font-medium text-muted-foreground">
                        {eyebrow}
                    </p>
                    <h1 className="mt-2 text-2xl font-bold">
                        {offer.headline}
                    </h1>
                    {offer.description && (
                        <p className="mt-2 text-muted-foreground">
                            {offer.description}
                        </p>
                    )}
                    <p className="mt-4 text-lg font-semibold">
                        {offer.product_name} — {formatPrice(offer.price)}
                    </p>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row sm:justify-center">
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
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="rounded-md bg-primary px-8 py-3 text-lg font-semibold text-primary-foreground shadow transition-colors hover:bg-primary/90 disabled:opacity-50"
                                >
                                    Ya, Saya Mau!
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
                                    className="rounded-md border px-8 py-3 text-muted-foreground transition-colors hover:bg-muted disabled:opacity-50"
                                >
                                    Tidak, terima kasih
                                </button>
                            </>
                        )}
                    </Form>
                </div>
            </main>
        </>
    );
}
