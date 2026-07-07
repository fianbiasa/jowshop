import { Head, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import MetaPixel from '@/components/meta-pixel';
import SiteLogo from '@/components/site-logo';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { MetaPixelEvent } from '@/lib/meta-pixel';
import { LOGO_WRAPPER_CLASS } from '@/lib/salespage-themes';
import { store } from '@/routes/public/checkout';
import { search as searchDestinations } from '@/routes/public/checkout/destinations';

const priceFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

function formatPrice(price: string) {
    return priceFormatter.format(Number(price));
}

type Destination = { id: string; label: string };

function parseDestinationLabel(label: string) {
    const parts = label.split(',').map((p) => p.trim());
    const postal = parts.find((p) => /^\d{4,6}$/.test(p)) ?? '';
    const rest = parts.filter((p) => p !== postal);

    return {
        district: rest[0] ?? '',
        city: rest[1] ?? '',
        province: rest[2] ?? '',
        postal_code: postal,
    };
}

export default function Checkout({
    funnel,
    product,
    metaPixel,
}: {
    funnel: { name: string; slug: string };
    product: { name: string; price: string; type: 'digital' | 'physical' };
    metaPixel: MetaPixelEvent | null;
}) {
    const form = useForm({
        name: '',
        email: '',
        phone: '',
        province: '',
        city: '',
        district: '',
        postal_code: '',
        destination_area_id: '',
        destination_label: '',
        address_line: '',
    });

    const [query, setQuery] = useState('');
    const [results, setResults] = useState<Destination[]>([]);
    const [showResults, setShowResults] = useState(false);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        debounceRef.current = setTimeout(() => {
            if (query.trim().length < 3) {
                setResults([]);

                return;
            }

            fetch(searchDestinations(funnel.slug, { query: { q: query } }).url)
                .then((res) => res.json())
                .then((body: { data: Destination[] }) => {
                    setResults(body.data ?? []);
                    setShowResults(true);
                })
                .catch(() => setResults([]));
        }, 300);

        return () => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
        };
    }, [query, funnel.slug]);

    function selectDestination(destination: Destination) {
        const parsed = parseDestinationLabel(destination.label);

        form.setData((data) => ({
            ...data,
            destination_area_id: destination.id,
            destination_label: destination.label,
            ...parsed,
        }));

        setQuery(destination.label);
        setShowResults(false);
    }

    function submit() {
        form.post(store(funnel.slug).url);
    }

    return (
        <>
            <Head title={`Checkout — ${funnel.name}`} />

            <MetaPixel event={metaPixel} />

            <main className="mx-auto max-w-xl space-y-8 px-4 py-12">
                <div className={LOGO_WRAPPER_CLASS.minimal}>
                    <SiteLogo style="minimal" />
                </div>

                <div className="text-center">
                    <h1 className="text-2xl font-bold">Checkout</h1>
                    <p className="mt-1 text-muted-foreground">
                        {product.name} · {formatPrice(product.price)}
                    </p>
                </div>

                <div className="space-y-6">
                    <div className="space-y-4 rounded-lg border p-4">
                        <h2 className="font-medium">Data Pembeli</h2>

                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama Lengkap</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="phone">Nomor WhatsApp</Label>
                            <Input
                                id="phone"
                                value={form.data.phone}
                                onChange={(e) =>
                                    form.setData('phone', e.target.value)
                                }
                                required
                            />
                            <InputError message={form.errors.phone} />
                        </div>
                    </div>

                    {product.type === 'physical' && (
                        <div className="space-y-4 rounded-lg border p-4">
                            <h2 className="font-medium">Alamat Pengiriman</h2>

                            <div className="relative grid gap-2">
                                <Label htmlFor="destination_search">
                                    Cari Kecamatan/Kota Tujuan
                                </Label>
                                <Input
                                    id="destination_search"
                                    value={query}
                                    onChange={(e) => {
                                        setQuery(e.target.value);
                                        form.setData('destination_area_id', '');
                                    }}
                                    onFocus={() =>
                                        results.length > 0 &&
                                        setShowResults(true)
                                    }
                                    placeholder="Ketik minimal 3 huruf, mis. Kebayoran"
                                />
                                {showResults && results.length > 0 && (
                                    <ul className="absolute top-full z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-popover shadow-md">
                                        {results.map((destination) => (
                                            <li key={destination.id}>
                                                <button
                                                    type="button"
                                                    className="w-full px-3 py-2 text-left text-sm hover:bg-muted"
                                                    onClick={() =>
                                                        selectDestination(
                                                            destination,
                                                        )
                                                    }
                                                >
                                                    {destination.label}
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                <InputError
                                    message={form.errors.destination_area_id}
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="province">Provinsi</Label>
                                    <Input
                                        id="province"
                                        value={form.data.province}
                                        onChange={(e) =>
                                            form.setData(
                                                'province',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                    <InputError
                                        message={form.errors.province}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="city">Kota/Kabupaten</Label>
                                    <Input
                                        id="city"
                                        value={form.data.city}
                                        onChange={(e) =>
                                            form.setData('city', e.target.value)
                                        }
                                        required
                                    />
                                    <InputError message={form.errors.city} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="district">Kecamatan</Label>
                                    <Input
                                        id="district"
                                        value={form.data.district}
                                        onChange={(e) =>
                                            form.setData(
                                                'district',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                    <InputError
                                        message={form.errors.district}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="postal_code">
                                        Kode Pos
                                    </Label>
                                    <Input
                                        id="postal_code"
                                        value={form.data.postal_code}
                                        onChange={(e) =>
                                            form.setData(
                                                'postal_code',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                    <InputError
                                        message={form.errors.postal_code}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address_line">
                                    Alamat Lengkap
                                </Label>
                                <Textarea
                                    id="address_line"
                                    value={form.data.address_line}
                                    onChange={(e) =>
                                        form.setData(
                                            'address_line',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={form.errors.address_line}
                                />
                            </div>
                        </div>
                    )}

                    <InputError
                        message={(form.errors as Record<string, string>).stock}
                    />

                    <button
                        type="button"
                        onClick={submit}
                        disabled={form.processing}
                        className="w-full rounded-md bg-primary px-6 py-3 text-lg font-semibold text-primary-foreground shadow transition-colors hover:bg-primary/90 disabled:opacity-50"
                    >
                        Lanjutkan
                    </button>
                </div>
            </main>
        </>
    );
}
