import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { dashboard } from '@/routes';

type FunnelStep = {
    event: string;
    label: string;
    count: number;
    rate: number;
};

type OfferTakeRate = {
    offer_id: number;
    headline: string;
    stage: string;
    view_count: number;
    accepted_count: number;
    take_rate: number;
};

type RevenueBreakdown = {
    offer_type: string;
    revenue: string;
};

type Summary = {
    visitor_count: number;
    order_count: number;
    revenue: string;
    funnel_steps: FunnelStep[];
    offers: OfferTakeRate[];
    revenue_breakdown: RevenueBreakdown[];
};

function formatPrice(price: string) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(price));
}

export default function Dashboard({
    summary,
    funnels,
    filters,
}: {
    summary: Summary;
    funnels: { id: number; name: string }[];
    filters: {
        funnel_id: number | null;
        from: string | null;
        to: string | null;
        utm_source: string | null;
    };
}) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Dashboard"
                    description="Ringkasan performa funnel: kunjungan, konversi tiap langkah, take rate offer, dan revenue"
                />

                <Form
                    {...dashboard.form()}
                    className="flex flex-wrap items-end gap-4"
                >
                    {({ processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="funnel_id">Funnel</Label>
                                <Select
                                    name="funnel_id"
                                    defaultValue={
                                        filters.funnel_id
                                            ? String(filters.funnel_id)
                                            : 'all'
                                    }
                                >
                                    <SelectTrigger
                                        id="funnel_id"
                                        className="w-48"
                                    >
                                        <SelectValue placeholder="Semua funnel" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Semua Funnel
                                        </SelectItem>
                                        {funnels.map((funnel) => (
                                            <SelectItem
                                                key={funnel.id}
                                                value={String(funnel.id)}
                                            >
                                                {funnel.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="from">Dari Tanggal</Label>
                                <Input
                                    id="from"
                                    name="from"
                                    type="date"
                                    defaultValue={filters.from ?? ''}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="to">Sampai Tanggal</Label>
                                <Input
                                    id="to"
                                    name="to"
                                    type="date"
                                    defaultValue={filters.to ?? ''}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="utm_source">
                                    Sumber Traffic (UTM)
                                </Label>
                                <Input
                                    id="utm_source"
                                    name="utm_source"
                                    placeholder="facebook, google, ..."
                                    defaultValue={filters.utm_source ?? ''}
                                />
                            </div>

                            <Button type="submit" disabled={processing}>
                                Terapkan Filter
                            </Button>
                        </>
                    )}
                </Form>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardDescription>
                                Visitor Ter-tracking
                            </CardDescription>
                            <CardTitle className="text-3xl">
                                {summary.visitor_count}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardDescription>Pesanan Terbayar</CardDescription>
                            <CardTitle className="text-3xl">
                                {summary.order_count}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardDescription>Revenue</CardDescription>
                            <CardTitle className="text-3xl">
                                {formatPrice(summary.revenue)}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Konversi Tiap Langkah</CardTitle>
                        <CardDescription>
                            Jumlah sesi funnel unik yang mencapai tiap langkah,
                            dan persentase relatif terhadap langkah pertama
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Langkah</TableHead>
                                    <TableHead>Jumlah Sesi</TableHead>
                                    <TableHead>Konversi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {summary.funnel_steps.map((step) => (
                                    <TableRow key={step.event}>
                                        <TableCell>{step.label}</TableCell>
                                        <TableCell>{step.count}</TableCell>
                                        <TableCell>{step.rate}%</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>
                            Take Rate Order Bump / Upsell / Downsell
                        </CardTitle>
                        <CardDescription>
                            Persentase offer yang diterima dari total yang
                            dilihat
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {summary.offers.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Belum ada offer untuk funnel ini.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Offer</TableHead>
                                        <TableHead>Tahap</TableHead>
                                        <TableHead>Dilihat</TableHead>
                                        <TableHead>Diterima</TableHead>
                                        <TableHead>Take Rate</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {summary.offers.map((offer) => (
                                        <TableRow key={offer.offer_id}>
                                            <TableCell>
                                                {offer.headline}
                                            </TableCell>
                                            <TableCell className="capitalize">
                                                {offer.stage}
                                            </TableCell>
                                            <TableCell>
                                                {offer.view_count}
                                            </TableCell>
                                            <TableCell>
                                                {offer.accepted_count}
                                            </TableCell>
                                            <TableCell>
                                                {offer.take_rate}%
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Revenue per Jenis Item</CardTitle>
                        <CardDescription>
                            Kontribusi revenue dari produk utama vs order bump
                            vs upsell vs downsell
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Jenis</TableHead>
                                    <TableHead>Revenue</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {summary.revenue_breakdown.map((row) => (
                                    <TableRow key={row.offer_type}>
                                        <TableCell className="capitalize">
                                            {row.offer_type}
                                        </TableCell>
                                        <TableCell>
                                            {formatPrice(row.revenue)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
