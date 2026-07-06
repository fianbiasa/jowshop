import { Form, Head } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    destinations as searchDestinations,
    store,
} from '@/routes/shipping-estimate';

const priceFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

function formatPrice(cost: number) {
    return priceFormatter.format(cost);
}

type Destination = { id: string; label: string };

type Rate = {
    courier: string;
    service: string;
    description: string;
    cost: number;
    etd: string;
};

export default function ShippingEstimate({
    products,
    rates,
    selectedProductId,
    destinationLabel,
}: {
    products: { id: number; name: string }[];
    rates?: Rate[];
    selectedProductId?: number;
    destinationLabel?: string | null;
}) {
    const [query, setQuery] = useState(destinationLabel ?? '');
    const [destinationAreaId, setDestinationAreaId] = useState('');
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

            fetch(searchDestinations({ query: { q: query } }).url)
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
    }, [query]);

    function selectDestination(destination: Destination) {
        setDestinationAreaId(destination.id);
        setQuery(destination.label);
        setShowResults(false);
    }

    return (
        <>
            <Head title="Cek Ongkir" />

            <main className="mx-auto max-w-lg space-y-6 px-4 py-12">
                <div>
                    <h1 className="text-2xl font-bold">Cek Ongkir</h1>
                    <p className="text-sm text-muted-foreground">
                        Pilih produk dan tujuan pengiriman untuk melihat
                        estimasi ongkirnya.
                    </p>
                </div>

                <Form {...store.form()} className="space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="product_id">Produk</Label>
                                <Select
                                    name="product_id"
                                    defaultValue={
                                        selectedProductId
                                            ? String(selectedProductId)
                                            : undefined
                                    }
                                >
                                    <SelectTrigger
                                        id="product_id"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Pilih produk" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {products.map((product) => (
                                            <SelectItem
                                                key={product.id}
                                                value={String(product.id)}
                                            >
                                                {product.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.product_id} />
                            </div>

                            <div className="relative grid gap-2">
                                <Label htmlFor="destination_search">
                                    Tujuan Pengiriman
                                </Label>
                                <Input
                                    id="destination_search"
                                    value={query}
                                    onChange={(e) => {
                                        setQuery(e.target.value);
                                        setDestinationAreaId('');
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
                                <input
                                    type="hidden"
                                    name="destination_area_id"
                                    value={destinationAreaId}
                                />
                                <input
                                    type="hidden"
                                    name="destination_label"
                                    value={query}
                                />
                                <InputError
                                    message={errors.destination_area_id}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={
                                    processing || destinationAreaId === ''
                                }
                            >
                                Cek Ongkir
                            </Button>
                        </>
                    )}
                </Form>

                {rates && (
                    <div className="space-y-2">
                        {rates.length === 0 ? (
                            <p className="text-center text-sm text-muted-foreground">
                                Tidak ada opsi pengiriman tersedia untuk tujuan
                                ini.
                            </p>
                        ) : (
                            rates.map((rate) => (
                                <div
                                    key={`${rate.courier}-${rate.service}`}
                                    className="flex items-center justify-between rounded-lg border p-4"
                                >
                                    <div>
                                        <p className="font-medium uppercase">
                                            {rate.courier} — {rate.service}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {rate.description}
                                            {rate.etd &&
                                                ` · Estimasi ${rate.etd} hari`}
                                        </p>
                                    </div>
                                    <p className="font-semibold">
                                        {formatPrice(rate.cost)}
                                    </p>
                                </div>
                            ))
                        )}
                    </div>
                )}
            </main>
        </>
    );
}
