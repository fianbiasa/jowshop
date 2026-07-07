import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import SiteLogo from '@/components/site-logo';
import { LOGO_WRAPPER_CLASS } from '@/lib/salespage-themes';
import { store } from '@/routes/public/checkout/pay';

const priceFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

function formatPrice(price: number) {
    return priceFormatter.format(price);
}

type PaymentMethod = {
    code: string;
    name: string;
    image: string;
    fee: number;
};

export default function CheckoutPayment({
    funnel,
    methods,
}: {
    funnel: { name: string; slug: string };
    methods: PaymentMethod[];
}) {
    const [selected, setSelected] = useState<PaymentMethod | null>(null);

    return (
        <>
            <Head title={`Pilih Pembayaran — ${funnel.name}`} />

            <main className="mx-auto max-w-xl space-y-6 px-4 py-12">
                <div className={LOGO_WRAPPER_CLASS.minimal}>
                    <SiteLogo style="minimal" />
                </div>

                <div className="text-center">
                    <h1 className="text-2xl font-bold">
                        Pilih Metode Pembayaran
                    </h1>
                </div>

                {methods.length === 0 && (
                    <p className="text-center text-sm text-muted-foreground">
                        Tidak ada metode pembayaran tersedia saat ini. Silakan
                        hubungi kami.
                    </p>
                )}

                <Form {...store.form(funnel.slug)} className="space-y-3">
                    {({ processing, errors }) => (
                        <>
                            <InputError message={errors.payment_method} />

                            {methods.map((method) => {
                                const isSelected =
                                    selected?.code === method.code;

                                return (
                                    <label
                                        key={method.code}
                                        className={`flex cursor-pointer items-center justify-between rounded-lg border p-4 hover:bg-muted ${isSelected ? 'border-primary' : ''}`}
                                    >
                                        <span className="flex items-center gap-3">
                                            <input
                                                type="radio"
                                                name="payment_method_choice"
                                                checked={isSelected}
                                                onChange={() =>
                                                    setSelected(method)
                                                }
                                                required
                                            />
                                            <img
                                                src={method.image}
                                                alt=""
                                                className="h-8 w-auto"
                                            />
                                            <span className="font-medium">
                                                {method.name}
                                            </span>
                                        </span>
                                        <span className="font-semibold">
                                            {method.fee > 0
                                                ? formatPrice(method.fee)
                                                : 'Gratis'}
                                        </span>
                                    </label>
                                );
                            })}

                            <input
                                type="hidden"
                                name="payment_method"
                                value={selected?.code ?? ''}
                            />

                            <button
                                type="submit"
                                disabled={processing || selected === null}
                                className="w-full rounded-md bg-primary px-6 py-3 text-lg font-semibold text-primary-foreground shadow transition-colors hover:bg-primary/90 disabled:opacity-50"
                            >
                                Bayar Sekarang
                            </button>
                        </>
                    )}
                </Form>
            </main>
        </>
    );
}
