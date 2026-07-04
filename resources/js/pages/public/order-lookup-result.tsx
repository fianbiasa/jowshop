import { Head } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type OrderStatus =
    | 'pending'
    | 'paid'
    | 'processing'
    | 'shipped'
    | 'completed'
    | 'cancelled'
    | 'expired';

type Item = {
    product_name: string;
    quantity: number;
    unit_price: string;
    is_digital: boolean;
    download_token: string | null;
};

function formatPrice(price: string) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(price));
}

export default function OrderLookupResult({
    order,
}: {
    order: {
        order_number: string;
        status: OrderStatus;
        total: string;
        items: Item[];
        shipment: {
            courier: string;
            service: string;
            status: string;
            tracking_number: string | null;
        } | null;
    };
}) {
    return (
        <>
            <Head title={`Pesanan ${order.order_number}`} />

            <main className="mx-auto max-w-xl space-y-6 px-4 py-12">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">
                            Pesanan {order.order_number}
                        </h1>
                    </div>
                    <Badge className="capitalize">{order.status}</Badge>
                </div>

                <div className="space-y-3">
                    {order.items.map((item, i) => (
                        <div
                            key={i}
                            className="flex items-center justify-between rounded-lg border p-4"
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
                                    <a href={`/unduh/${item.download_token}`}>
                                        Unduh
                                    </a>
                                </Button>
                            )}
                        </div>
                    ))}
                </div>

                {order.shipment && (
                    <div className="rounded-lg border p-4">
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
        </>
    );
}
