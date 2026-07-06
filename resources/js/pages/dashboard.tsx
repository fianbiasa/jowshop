import { Form, Head } from '@inertiajs/react';
import { Eye, ShoppingCart, TrendingUp, Wallet } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
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
import { cn } from '@/lib/utils';
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

type TrafficSource = {
    source: string;
    visitor_count: number;
    order_count: number;
    revenue: string;
    conversion_rate: number;
};

type PageView = {
    funnel_id: number;
    name: string;
    slug: string;
    view_count: number;
};

type BestSellingProduct = {
    product_id: number;
    name: string;
    quantity_sold: number;
    revenue: string;
};

type Summary = {
    visitor_count: number;
    order_count: number;
    revenue: string;
    funnel_steps: FunnelStep[];
    offers: OfferTakeRate[];
    revenue_breakdown: RevenueBreakdown[];
    traffic_sources: TrafficSource[];
    page_views: PageView[];
    best_selling_products: BestSellingProduct[];
};

const priceFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

function formatPrice(price: string) {
    return priceFormatter.format(Number(price));
}

const STAT_ACCENT_CLASS = {
    blue: 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400',
    violet: 'bg-violet-50 text-violet-600 dark:bg-violet-950/40 dark:text-violet-400',
    green: 'bg-green-50 text-green-600 dark:bg-green-950/40 dark:text-green-400',
    amber: 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400',
} as const;

function StatCard({
    label,
    value,
    icon: Icon,
    accent,
}: {
    label: string;
    value: string;
    icon: LucideIcon;
    accent: keyof typeof STAT_ACCENT_CLASS;
}) {
    return (
        <Card>
            <CardHeader className="flex-row items-center justify-between gap-4 space-y-0">
                <div>
                    <CardDescription>{label}</CardDescription>
                    <CardTitle className="mt-1.5 text-3xl">{value}</CardTitle>
                </div>
                <div
                    className={cn(
                        'flex size-11 shrink-0 items-center justify-center rounded-full',
                        STAT_ACCENT_CLASS[accent],
                    )}
                >
                    <Icon className="size-5" />
                </div>
            </CardHeader>
        </Card>
    );
}

function RateBar({
    rate,
    colorClass = 'bg-primary',
}: {
    rate: number;
    colorClass?: string;
}) {
    return (
        <div className="flex items-center gap-2">
            <div className="h-1.5 w-24 overflow-hidden rounded-full bg-muted">
                <div
                    className={cn('h-full rounded-full', colorClass)}
                    style={{ width: `${Math.min(100, Math.max(0, rate))}%` }}
                />
            </div>
            <span className="text-sm tabular-nums">{rate}%</span>
        </div>
    );
}

const STAGE_BADGE_CLASS: Record<string, string> = {
    bump: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-400',
    upsell: 'border-green-200 bg-green-50 text-green-700 dark:border-green-900 dark:bg-green-950/40 dark:text-green-400',
    downsell:
        'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-400',
};

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
    const maxRevenue = Math.max(
        ...summary.revenue_breakdown.map((row) => Number(row.revenue)),
        1,
    );
    const maxPageViews = Math.max(
        ...summary.page_views.map((row) => row.view_count),
        1,
    );
    const maxQuantitySold = Math.max(
        ...summary.best_selling_products.map((row) => row.quantity_sold),
        1,
    );

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

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        label="Visitor Ter-tracking"
                        value={summary.visitor_count.toLocaleString('id-ID')}
                        icon={Eye}
                        accent="blue"
                    />
                    <StatCard
                        label="Pesanan Terbayar"
                        value={summary.order_count.toLocaleString('id-ID')}
                        icon={ShoppingCart}
                        accent="violet"
                    />
                    <StatCard
                        label="Revenue"
                        value={formatPrice(summary.revenue)}
                        icon={Wallet}
                        accent="green"
                    />
                    <StatCard
                        label="Konversi Akhir"
                        value={`${summary.funnel_steps.at(-1)?.rate ?? 0}%`}
                        icon={TrendingUp}
                        accent="amber"
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Sumber Traffic</CardTitle>
                        <CardDescription>
                            Dari mana visitor datang: iklan (Google/Facebook/
                            dll), organik, atau langsung
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {summary.traffic_sources.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Belum ada visitor untuk filter ini.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Sumber</TableHead>
                                        <TableHead>Visitor</TableHead>
                                        <TableHead>Order</TableHead>
                                        <TableHead>Revenue</TableHead>
                                        <TableHead>Konversi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {summary.traffic_sources.map((row) => (
                                        <TableRow key={row.source}>
                                            <TableCell className="font-medium">
                                                {row.source}
                                            </TableCell>
                                            <TableCell>
                                                {row.visitor_count}
                                            </TableCell>
                                            <TableCell>
                                                {row.order_count}
                                            </TableCell>
                                            <TableCell>
                                                {formatPrice(row.revenue)}
                                            </TableCell>
                                            <TableCell>
                                                <RateBar
                                                    rate={row.conversion_rate}
                                                />
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
                        <CardTitle>Halaman Paling Banyak Dikunjungi</CardTitle>
                        <CardDescription>
                            Salespage funnel mana yang paling sering dilihat
                            visitor
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {summary.page_views.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Belum ada kunjungan untuk filter ini.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Halaman</TableHead>
                                        <TableHead>URL</TableHead>
                                        <TableHead>Kunjungan</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {summary.page_views.map((row) => (
                                        <TableRow key={row.funnel_id}>
                                            <TableCell className="font-medium">
                                                {row.name}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                /f/{row.slug}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <div className="h-1.5 w-24 overflow-hidden rounded-full bg-muted">
                                                        <div
                                                            className="h-full rounded-full bg-blue-500"
                                                            style={{
                                                                width: `${(row.view_count / maxPageViews) * 100}%`,
                                                            }}
                                                        />
                                                    </div>
                                                    <span className="text-sm tabular-nums">
                                                        {row.view_count}
                                                    </span>
                                                </div>
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
                                        <TableCell className="font-medium">
                                            {step.label}
                                        </TableCell>
                                        <TableCell>{step.count}</TableCell>
                                        <TableCell>
                                            <RateBar rate={step.rate} />
                                        </TableCell>
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
                                            <TableCell className="font-medium">
                                                {offer.headline}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant="outline"
                                                    className={cn(
                                                        'capitalize',
                                                        STAGE_BADGE_CLASS[
                                                            offer.stage
                                                        ],
                                                    )}
                                                >
                                                    {offer.stage}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {offer.view_count}
                                            </TableCell>
                                            <TableCell>
                                                {offer.accepted_count}
                                            </TableCell>
                                            <TableCell>
                                                <RateBar
                                                    rate={offer.take_rate}
                                                />
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
                                    <TableHead>Kontribusi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {summary.revenue_breakdown.map((row) => (
                                    <TableRow key={row.offer_type}>
                                        <TableCell className="font-medium capitalize">
                                            {row.offer_type}
                                        </TableCell>
                                        <TableCell>
                                            {formatPrice(row.revenue)}
                                        </TableCell>
                                        <TableCell>
                                            <RateBar
                                                rate={Math.round(
                                                    (Number(row.revenue) /
                                                        maxRevenue) *
                                                        100,
                                                )}
                                                colorClass="bg-green-500"
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Produk Terlaris</CardTitle>
                        <CardDescription>
                            Produk dengan unit terjual terbanyak, gabungan dari
                            produk utama, order bump, upsell, dan downsell
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {summary.best_selling_products.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Belum ada produk terjual untuk filter ini.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Produk</TableHead>
                                        <TableHead>Terjual</TableHead>
                                        <TableHead>Revenue</TableHead>
                                        <TableHead>Kontribusi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {summary.best_selling_products.map(
                                        (row) => (
                                            <TableRow key={row.product_id}>
                                                <TableCell className="font-medium">
                                                    {row.name}
                                                </TableCell>
                                                <TableCell>
                                                    {row.quantity_sold}
                                                </TableCell>
                                                <TableCell>
                                                    {formatPrice(row.revenue)}
                                                </TableCell>
                                                <TableCell>
                                                    <RateBar
                                                        rate={Math.round(
                                                            (row.quantity_sold /
                                                                maxQuantitySold) *
                                                                100,
                                                        )}
                                                        colorClass="bg-violet-500"
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ),
                                    )}
                                </TableBody>
                            </Table>
                        )}
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
