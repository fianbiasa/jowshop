import { Head } from '@inertiajs/react';
import OrderStatusTimeline from '@/components/order-status-timeline';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    CARD_CLASS,
    CTA_BUTTON_CLASS,
    PAGE_BG_CLASS,
} from '@/lib/salespage-themes';
import type { SalespageStyle as Style } from '@/types/models';

const TITLE_CLASS: Record<Style, string> = {
    minimal: 'text-2xl font-bold',
    bold: 'text-2xl font-extrabold text-stone-900',
    editorial: 'font-serif text-2xl font-bold text-stone-900',
    ledger: 'font-mono text-xl font-bold text-stone-900',
};

type OrderStatus =
    | 'pending'
    | 'paid'
    | 'processing'
    | 'shipped'
    | 'completed'
    | 'cancelled'
    | 'expired';

const TIMELINE_STATUSES: OrderStatus[] = [
    'paid',
    'processing',
    'shipped',
    'completed',
];

type Item = {
    id: number;
    product_name: string;
    quantity: number;
    unit_price: string;
    is_digital: boolean;
    download_token: string | null;
};

const priceFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

function formatPrice(price: string) {
    return priceFormatter.format(Number(price));
}

export default function OrderLookupResult({
    style,
    order,
}: {
    style: Style;
    order: {
        order_number: string;
        status: OrderStatus;
        total: string;
        payment_resume_url: string | null;
        items: Item[];
        shipment: {
            courier: string;
            service: string;
            status: string;
            tracking_number: string | null;
        } | null;
    };
}) {
    const showTimeline = TIMELINE_STATUSES.includes(order.status);

    return (
        <>
            <Head title={`Pesanan ${order.order_number}`} />

            <div className={`min-h-screen ${PAGE_BG_CLASS[style]}`}>
                <main className="mx-auto max-w-xl space-y-6 px-4 py-12">
                    <div className="flex items-center justify-between">
                        <h1 className={TITLE_CLASS[style]}>
                            Pesanan {order.order_number}
                        </h1>
                        <Badge className="capitalize">{order.status}</Badge>
                    </div>

                    {showTimeline && (
                        <OrderStatusTimeline
                            status={order.status}
                            style={style}
                        />
                    )}

                    {order.payment_resume_url && (
                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p className="text-sm text-amber-800">
                                Pesanan ini masih menunggu pembayaran.
                            </p>
                            <a
                                href={order.payment_resume_url}
                                className={`mt-3 block text-center ${CTA_BUTTON_CLASS[style]}`}
                            >
                                Lanjutkan Pembayaran
                            </a>
                        </div>
                    )}

                    <div className="space-y-3">
                        {order.items.map((item) => (
                            <div
                                key={item.id}
                                className={`flex items-center justify-between ${CARD_CLASS[style]}`}
                            >
                                <div>
                                    <p className="text-sm font-medium">
                                        {item.product_name} × {item.quantity}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {formatPrice(item.unit_price)}
                                    </p>
                                </div>
                                {item.is_digital && item.download_token && (
                                    <Button asChild size="sm">
                                        <a
                                            href={`/unduh/${item.download_token}`}
                                        >
                                            Unduh
                                        </a>
                                    </Button>
                                )}
                            </div>
                        ))}
                    </div>

                    {order.shipment && (
                        <div className={CARD_CLASS[style]}>
                            <p className="text-sm font-medium">
                                Pengiriman — {order.shipment.courier}{' '}
                                {order.shipment.service}
                            </p>
                            <p className="text-sm text-muted-foreground capitalize">
                                Status: {order.shipment.status}
                            </p>
                            {order.shipment.tracking_number && (
                                <p className="text-sm text-muted-foreground">
                                    Nomor Resi: {order.shipment.tracking_number}
                                </p>
                            )}
                        </div>
                    )}

                    <div className="flex items-center justify-between border-t pt-4 font-semibold">
                        <span>Total</span>
                        <span>{formatPrice(order.total)}</span>
                    </div>
                </main>
            </div>
        </>
    );
}
