import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { store } from '@/routes/public/checkout/shipping';

function formatPrice(price: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(price);
}

type Rate = {
    courier: string;
    service: string;
    description: string;
    cost: number;
    etd: string;
};

export default function CheckoutShipping({
    funnel,
    rates,
}: {
    funnel: { name: string; slug: string };
    rates: Rate[];
}) {
    const [selected, setSelected] = useState<Rate | null>(null);

    return (
        <>
            <Head title={`Pilih Pengiriman — ${funnel.name}`} />

            <main className="mx-auto max-w-xl space-y-6 px-4 py-12">
                <div className="text-center">
                    <h1 className="text-2xl font-bold">
                        Pilih Metode Pengiriman
                    </h1>
                </div>

                {rates.length === 0 && (
                    <p className="text-center text-sm text-muted-foreground">
                        Tidak ada opsi pengiriman tersedia untuk alamat ini.
                        Silakan hubungi kami.
                    </p>
                )}

                <Form {...store.form(funnel.slug)} className="space-y-3">
                    {({ processing, errors }) => (
                        <>
                            <InputError message={errors.courier} />

                            {rates.map((rate) => {
                                const key = `${rate.courier}-${rate.service}`;
                                const isSelected =
                                    selected?.courier === rate.courier &&
                                    selected?.service === rate.service;

                                return (
                                    <label
                                        key={key}
                                        className={`flex cursor-pointer items-center justify-between rounded-lg border p-4 hover:bg-muted ${isSelected ? 'border-primary' : ''}`}
                                    >
                                        <span className="flex items-center gap-3">
                                            <input
                                                type="radio"
                                                name="courier_service_choice"
                                                checked={isSelected}
                                                onChange={() =>
                                                    setSelected(rate)
                                                }
                                                required
                                            />
                                            <span>
                                                <span className="block font-medium uppercase">
                                                    {rate.courier} —{' '}
                                                    {rate.service}
                                                </span>
                                                <span className="block text-sm text-muted-foreground">
                                                    {rate.description}
                                                    {rate.etd &&
                                                        ` · Estimasi ${rate.etd} hari`}
                                                </span>
                                            </span>
                                        </span>
                                        <span className="font-semibold">
                                            {formatPrice(rate.cost)}
                                        </span>
                                    </label>
                                );
                            })}

                            <input
                                type="hidden"
                                name="courier"
                                value={selected?.courier ?? ''}
                            />
                            <input
                                type="hidden"
                                name="service"
                                value={selected?.service ?? ''}
                            />

                            <button
                                type="submit"
                                disabled={processing || selected === null}
                                className="w-full rounded-md bg-primary px-6 py-3 text-lg font-semibold text-primary-foreground shadow transition-colors hover:bg-primary/90 disabled:opacity-50"
                            >
                                Lanjutkan ke Pembayaran
                            </button>
                        </>
                    )}
                </Form>
            </main>
        </>
    );
}
