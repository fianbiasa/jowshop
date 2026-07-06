import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index } from '@/routes/orders';
import { update as updateShipment } from '@/routes/orders/shipment';
import type { Order } from '@/types/models';

const priceFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

function formatPrice(price: string) {
    return priceFormatter.format(Number(price));
}

export default function Show({ order }: { order: Order }) {
    return (
        <>
            <Head title={`Pesanan ${order.order_number}`} />

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <Heading
                    title={`Pesanan ${order.order_number}`}
                    description={`Funnel: ${order.funnel?.name ?? '-'}`}
                />

                <section className="space-y-3 rounded-lg border p-4">
                    <h2 className="font-medium">Pelanggan</h2>
                    <p className="text-sm">
                        {order.customer?.name} · {order.customer?.email} ·{' '}
                        {order.customer?.phone}
                    </p>

                    {order.address && (
                        <div className="text-sm text-muted-foreground">
                            {order.address.recipient_name} (
                            {order.address.phone})
                            <br />
                            {order.address.address_line}
                            <br />
                            {order.address.destination_label ??
                                `${order.address.district}, ${order.address.city}, ${order.address.province} ${order.address.postal_code}`}
                        </div>
                    )}
                </section>

                <section className="space-y-3 rounded-lg border p-4">
                    <h2 className="font-medium">Item Pesanan</h2>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Produk</TableHead>
                                <TableHead>Tipe</TableHead>
                                <TableHead>Qty</TableHead>
                                <TableHead className="text-right">
                                    Harga
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {order.items?.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell>{item.product?.name}</TableCell>
                                    <TableCell className="capitalize">
                                        {item.offer_type}
                                    </TableCell>
                                    <TableCell>{item.quantity}</TableCell>
                                    <TableCell className="text-right">
                                        {formatPrice(item.unit_price)}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    <div className="space-y-1 border-t pt-3 text-sm">
                        <div className="flex justify-between">
                            <span>Subtotal</span>
                            <span>{formatPrice(order.subtotal)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span>Ongkir</span>
                            <span>{formatPrice(order.shipping_cost)}</span>
                        </div>
                        <div className="flex justify-between font-semibold">
                            <span>Total</span>
                            <span>{formatPrice(order.total)}</span>
                        </div>
                    </div>
                </section>

                <section className="space-y-3 rounded-lg border p-4">
                    <h2 className="font-medium">Pembayaran</h2>
                    {order.payments?.length ? (
                        <div className="space-y-2">
                            {order.payments.map((payment) => (
                                <div
                                    key={payment.id}
                                    className="flex items-center justify-between text-sm"
                                >
                                    <span className="font-mono">
                                        {payment.merchant_order_id}
                                    </span>
                                    <span>{formatPrice(payment.amount)}</span>
                                    <Badge
                                        variant={
                                            payment.status === 'paid'
                                                ? 'default'
                                                : 'outline'
                                        }
                                        className="capitalize"
                                    >
                                        {payment.status}
                                    </Badge>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            Belum ada pembayaran.
                        </p>
                    )}
                </section>

                {order.shipment && (
                    <section className="space-y-4 rounded-lg border p-4">
                        <h2 className="font-medium">Pengiriman</h2>
                        <p className="text-sm text-muted-foreground uppercase">
                            {order.shipment.courier} — {order.shipment.service}{' '}
                            · {formatPrice(order.shipment.cost)}
                        </p>

                        <Form
                            {...updateShipment.form(order.id)}
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="tracking_number">
                                            Nomor Resi
                                        </Label>
                                        <Input
                                            id="tracking_number"
                                            name="tracking_number"
                                            defaultValue={
                                                order.shipment
                                                    ?.tracking_number ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.tracking_number}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="status">
                                            Status Pengiriman
                                        </Label>
                                        <Select
                                            name="status"
                                            defaultValue={
                                                order.shipment?.status
                                            }
                                        >
                                            <SelectTrigger
                                                id="status"
                                                className="w-full"
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="pending">
                                                    Pending
                                                </SelectItem>
                                                <SelectItem value="processing">
                                                    Processing
                                                </SelectItem>
                                                <SelectItem value="shipped">
                                                    Shipped
                                                </SelectItem>
                                                <SelectItem value="delivered">
                                                    Delivered
                                                </SelectItem>
                                                <SelectItem value="failed">
                                                    Failed
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.status} />
                                    </div>

                                    <Button disabled={processing}>
                                        Simpan Pengiriman
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>
                )}
            </div>
        </>
    );
}

Show.layout = {
    breadcrumbs: [
        { title: 'Pesanan', href: index() },
        { title: 'Detail', href: index() },
    ],
};
