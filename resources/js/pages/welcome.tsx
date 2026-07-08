import { Head, Link, usePage } from '@inertiajs/react';
import { BarChart3, CreditCard, GitBranch, Sparkles } from 'lucide-react';
import SiteLogo from '@/components/site-logo';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';
import legal from '@/routes/legal';
import { create as orderLookupCreate } from '@/routes/order-lookup';
import { create as shippingEstimateCreate } from '@/routes/shipping-estimate';
import { create as trackingCreate } from '@/routes/tracking';

const customerLinks = [
    { label: 'Cek Pesanan', href: orderLookupCreate() },
    { label: 'Cek Ongkir', href: shippingEstimateCreate() },
    { label: 'Resi', href: trackingCreate() },
];

const footerLinks = [
    { label: 'Syarat & Ketentuan', href: legal.terms() },
    { label: 'Kebijakan Privasi', href: legal.privacy() },
    { label: 'Kontak', href: legal.contact() },
];

const features = [
    {
        icon: Sparkles,
        title: 'Salespage dengan AI',
        description:
            'Pilih style, kasih brief singkat, biarkan AI yang tulis copy salespage-nya.',
    },
    {
        icon: GitBranch,
        title: 'Order Bump, Upsell & Downsell',
        description:
            'Susun funnel penjualan lengkap untuk memaksimalkan nilai tiap transaksi.',
    },
    {
        icon: CreditCard,
        title: 'Checkout & Pembayaran',
        description:
            'Checkout siap pakai dengan integrasi pembayaran Duitku, termasuk pengiriman.',
    },
    {
        icon: BarChart3,
        title: 'Dashboard Analitik',
        description:
            'Pantau kunjungan, konversi tiap langkah funnel, dan revenue secara real-time.',
    },
];

export default function Welcome() {
    const { auth, branding } = usePage().props;

    return (
        <>
            <Head title={`${branding.siteName} — Platform Sales Funnel`}>
                <meta
                    name="description"
                    content={`${branding.siteName} membantu kamu menyusun salespage, checkout, order bump, upsell, dan downsell dalam satu funnel penjualan — lengkap dengan dashboard analitik dan pembayaran terintegrasi.`}
                />
            </Head>

            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <div className="border-b bg-muted/30">
                    <nav className="mx-auto flex w-full max-w-5xl items-center justify-end gap-4 px-6 py-2 text-sm text-muted-foreground">
                        {customerLinks.map((link) => (
                            <Link
                                key={link.label}
                                href={link.href}
                                className="hover:text-foreground hover:underline"
                            >
                                {link.label}
                            </Link>
                        ))}
                    </nav>
                </div>

                <header className="mx-auto flex w-full max-w-5xl items-center justify-between p-6">
                    <div className="font-semibold">
                        <SiteLogo style="minimal" />
                    </div>

                    <nav className="flex items-center gap-3">
                        {auth.user ? (
                            <Button asChild>
                                <Link href={dashboard()}>Buka Dashboard</Link>
                            </Button>
                        ) : (
                            <Button variant="ghost" asChild>
                                <Link href={login()}>Masuk</Link>
                            </Button>
                        )}
                    </nav>
                </header>

                <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col items-center justify-center gap-16 px-6 py-16 text-center">
                    <div className="space-y-6">
                        <h1 className="text-4xl font-bold tracking-tight sm:text-5xl">
                            Bangun Funnel Penjualan yang Benar-Benar Mengonversi
                        </h1>
                        <p className="mx-auto max-w-2xl text-lg text-muted-foreground">
                            Jowshop membantu kamu menyusun salespage, checkout,
                            order bump, upsell/downsell, sampai pembayaran —
                            dalam satu funnel yang siap pakai.
                        </p>
                        <div className="flex items-center justify-center gap-3">
                            {auth.user ? (
                                <Button size="lg" asChild>
                                    <Link href={dashboard()}>
                                        Buka Dashboard
                                    </Link>
                                </Button>
                            ) : (
                                <Button size="lg" asChild>
                                    <Link href={login()}>Masuk</Link>
                                </Button>
                            )}
                        </div>
                    </div>

                    <div className="grid w-full gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {features.map((feature) => (
                            <div
                                key={feature.title}
                                className="flex flex-col items-center gap-3 rounded-lg border p-6 text-center"
                            >
                                <feature.icon className="size-6 text-primary" />
                                <div className="font-medium">
                                    {feature.title}
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {feature.description}
                                </p>
                            </div>
                        ))}
                    </div>
                </main>

                <footer className="border-t p-6">
                    <nav className="flex flex-wrap items-center justify-center gap-4 text-sm text-muted-foreground">
                        {footerLinks.map((link) => (
                            <Link
                                key={link.label}
                                href={link.href}
                                className="hover:text-foreground hover:underline"
                            >
                                {link.label}
                            </Link>
                        ))}
                    </nav>
                    <p className="mt-4 text-center text-sm text-muted-foreground">
                        &copy; {new Date().getFullYear()} {branding.siteName}
                    </p>
                </footer>
            </div>
        </>
    );
}
