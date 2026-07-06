import { Form, Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { index, show } from '@/routes/orders';
import type { Order, OrderStatus, Paginated } from '@/types/models';

const statusOptions: OrderStatus[] = [
    'pending',
    'paid',
    'processing',
    'shipped',
    'completed',
    'cancelled',
    'expired',
];

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

const priceFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

function formatPrice(price: string) {
    return priceFormatter.format(Number(price));
}

export default function Index({
    orders,
    filters,
}: {
    orders: Paginated<Order>;
    filters: { status: OrderStatus | null };
}) {
    return (
        <>
            <Head title="Pesanan" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Pesanan"
                    description="Daftar semua pesanan dari seluruh funnel"
                />

                <Form {...index.form()} className="flex items-end gap-4">
                    {({ processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Select
                                    name="status"
                                    defaultValue={filters.status ?? 'all'}
                                    disabled={processing}
                                >
                                    <SelectTrigger className="w-48">
                                        <SelectValue placeholder="Semua status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Semua Status
                                        </SelectItem>
                                        {statusOptions.map((status) => (
                                            <SelectItem
                                                key={status}
                                                value={status}
                                            >
                                                {status}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <Button type="submit" disabled={processing}>
                                Terapkan
                            </Button>
                        </>
                    )}
                </Form>

                {orders.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Belum ada pesanan.
                    </p>
                ) : (
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nomor Pesanan</TableHead>
                                    <TableHead>Pelanggan</TableHead>
                                    <TableHead>Funnel</TableHead>
                                    <TableHead>Total</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {orders.data.map((order) => (
                                    <TableRow key={order.id}>
                                        <TableCell className="font-mono text-sm">
                                            {order.order_number}
                                        </TableCell>
                                        <TableCell>
                                            {order.customer?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {order.funnel?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {formatPrice(order.total)}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariant[order.status]
                                                }
                                                className="capitalize"
                                            >
                                                {order.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={show(order.id)}>
                                                    Detail
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                {orders.last_page > 1 && (
                    <div className="flex flex-wrap gap-2">
                        {orders.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                asChild={!!link.url}
                            >
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ) : (
                                    <span
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                )}
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [{ title: 'Pesanan', href: index() }],
};
