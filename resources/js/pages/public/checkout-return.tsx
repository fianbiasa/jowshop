import { Head, Link } from '@inertiajs/react';
import { Mail, Package } from 'lucide-react';
import MetaPixel from '@/components/meta-pixel';
import type { MetaPixelEvent } from '@/components/meta-pixel';
import { Badge } from '@/components/ui/badge';

type OrderStatus =
    | 'pending'
    | 'paid'
    | 'processing'
    | 'shipped'
    | 'completed'
    | 'cancelled'
    | 'expired';

const NEXT_STEPS_STATUSES: OrderStatus[] = [
    'paid',
    'processing',
    'shipped',
    'completed',
];

function formatPrice(price: string) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(price));
}

const statusMessage: Record<OrderStatus, string> = {
    pending: 'Kami sedang menunggu konfirmasi pembayaranmu dari Duitku.',
    paid: 'Pembayaran berhasil! Kami akan segera memproses pesananmu.',
    processing: 'Pembayaran berhasil, pesananmu sedang diproses.',
    shipped: 'Pesananmu sudah dikirim.',
    completed: 'Pesananmu sudah selesai.',
    cancelled: 'Pesanan ini dibatalkan.',
    expired: 'Waktu pembayaran untuk pesanan ini sudah habis.',
};

const statusVariant: Record<
    OrderStatus,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    pending: 'outline',
    paid: 'default',
    processing: 'default',
    shipped: 'default',
    completed: 'default',
    cancelled: 'destructive',
    expired: 'destructive',
};

export default function CheckoutReturn({
    funnel,
    order,
    thankYouMessage,
    metaPixel,
    customerEmail,
    orderLookupUrl,
}: {
    funnel: { name: string };
    order: {
        order_number: string;
        status: OrderStatus;
        total: string;
        items: {
            product_name: string;
            quantity: number;
            unit_price: string;
            is_digital: boolean;
        }[];
    };
    thankYouMessage: string | null;
    metaPixel: MetaPixelEvent | null;
    customerEmail: string;
    orderLookupUrl: string;
}) {
    const showNextSteps = NEXT_STEPS_STATUSES.includes(order.status);
    const hasDigitalItem = order.items.some((item) => item.is_digital);
    const hasPhysicalItem = order.items.some((item) => !item.is_digital);

    return (
        <>
            <Head title={`Status Pesanan — ${funnel.name}`} />

            <MetaPixel event={metaPixel} />

            <main className="mx-auto max-w-xl space-y-8 px-4 py-12 text-center">
                <div>
                    <h1 className="text-2xl font-bold">
                        Terima kasih atas pesananmu!
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        {order.status === 'paid'
                            ? (thankYouMessage ?? statusMessage[order.status])
                            : statusMessage[order.status]}
                    </p>
                </div>

                <div className="rounded-lg border p-6 text-left">
                    <div className="mb-4 flex items-center justify-between border-b pb-4">
                        <span className="font-medium">Nomor Pesanan</span>
                        <span className="font-mono">{order.order_number}</span>
                    </div>

                    <div className="mb-4 flex items-center justify-between">
                        <span className="font-medium">Status</span>
                        <Badge
                            variant={statusVariant[order.status]}
                            className="capitalize"
                        >
                            {order.status}
                        </Badge>
                    </div>

                    <div className="space-y-2">
                        {order.items.map((item, i) => (
                            <div
                                key={i}
                                className="flex items-center justify-between text-sm"
                            >
                                <span>
                                    {item.product_name} × {item.quantity}
                                </span>
                                <span>{formatPrice(item.unit_price)}</span>
                            </div>
                        ))}
                    </div>

                    <div className="mt-4 flex items-center justify-between border-t pt-4 font-semibold">
                        <span>Total</span>
                        <span>{formatPrice(order.total)}</span>
                    </div>
                </div>

                {showNextSteps && (
                    <div className="space-y-4 rounded-lg border bg-muted/30 p-6 text-left">
                        <h2 className="font-semibold">Langkah Selanjutnya</h2>

                        {hasDigitalItem && (
                            <div className="flex gap-3 text-sm">
                                <Mail className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                <p>
                                    Produk digital: cek email di{' '}
                                    <span className="font-medium">
                                        {customerEmail}
                                    </span>{' '}
                                    untuk link download, lisensi (jika ada), dan
                                    instruksi akses. Link berlaku 30 hari sejak
                                    dikirim.
                                </p>
                            </div>
                        )}

                        {hasPhysicalItem && (
                            <div className="flex gap-3 text-sm">
                                <Package className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                <p>
                                    Produk fisik: pesananmu sedang kami siapkan.
                                    Nomor resi akan dikirim ke email begitu
                                    paket dikirim.
                                </p>
                            </div>
                        )}
                    </div>
                )}

                <Link
                    href={orderLookupUrl}
                    className="inline-flex items-center justify-center rounded-md border px-6 py-2.5 text-sm font-medium transition-colors hover:bg-muted"
                >
                    Cek Status Pesanan
                </Link>
            </main>
        </>
    );
}
