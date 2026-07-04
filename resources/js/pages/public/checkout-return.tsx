import { Head } from '@inertiajs/react';
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
}: {
    funnel: { name: string };
    order: {
        order_number: string;
        status: OrderStatus;
        total: string;
        items: { product_name: string; quantity: number; unit_price: string }[];
    };
    thankYouMessage: string | null;
    metaPixel: MetaPixelEvent | null;
}) {
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
            </main>
        </>
    );
}
