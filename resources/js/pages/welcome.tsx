import { Head, Link, usePage } from '@inertiajs/react';
import { PackageOpen } from 'lucide-react';
import SiteLogo from '@/components/site-logo';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';
import legal from '@/routes/legal';
import { create as orderLookupCreate } from '@/routes/order-lookup';
import { show as salespageShow } from '@/routes/public/salespage';
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
    { label: 'Kebijakan Refund', href: legal.refundPolicy() },
    { label: 'Kontak', href: legal.contact() },
];

const priceFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

type FunnelListing = {
    name: string;
    slug: string;
    price: string;
    thumbnailUrl: string | null;
};

export default function Welcome({ funnels }: { funnels: FunnelListing[] }) {
    const { auth, branding } = usePage().props;

    return (
        <>
            <Head title={branding.siteName}>
                <meta
                    name="description"
                    content={`${branding.siteName} — belanja produk pilihan kami secara online.`}
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
                        {auth.check ? (
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

                <main className="mx-auto w-full max-w-5xl flex-1 space-y-10 px-6 py-12">
                    <div className="space-y-2 text-center">
                        <h1 className="text-3xl font-bold tracking-tight">
                            {branding.siteName}
                        </h1>
                        <p className="mx-auto max-w-xl text-muted-foreground">
                            Belanja produk pilihan kami secara online. Cek
                            koleksi produk kami di bawah ini.
                        </p>
                    </div>

                    {funnels.length > 0 ? (
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {funnels.map((funnel) => (
                                <div
                                    key={funnel.slug}
                                    className="flex flex-col overflow-hidden rounded-lg border"
                                >
                                    <div className="aspect-square w-full bg-muted">
                                        {funnel.thumbnailUrl && (
                                            <img
                                                src={funnel.thumbnailUrl}
                                                alt={funnel.name}
                                                className="h-full w-full object-cover"
                                            />
                                        )}
                                    </div>
                                    <div className="flex flex-1 flex-col gap-3 p-4">
                                        <div className="flex-1 space-y-1">
                                            <h2 className="font-medium">
                                                {funnel.name}
                                            </h2>
                                            <p className="text-sm text-muted-foreground">
                                                {priceFormatter.format(
                                                    Number(funnel.price),
                                                )}
                                            </p>
                                        </div>
                                        <Button asChild>
                                            <Link
                                                href={salespageShow(
                                                    funnel.slug,
                                                )}
                                            >
                                                Lihat Produk
                                            </Link>
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="flex flex-col items-center gap-3 rounded-lg border border-dashed p-12 text-center text-muted-foreground">
                            <PackageOpen className="size-8" />
                            <p>Belum ada produk yang dipublikasikan.</p>
                        </div>
                    )}
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
                    {(branding.address || branding.email || branding.phone) && (
                        <p className="mt-4 text-center text-sm text-muted-foreground">
                            {[branding.address, branding.email, branding.phone]
                                .filter(Boolean)
                                .join(' · ')}
                        </p>
                    )}
                    <p className="mt-2 text-center text-sm text-muted-foreground">
                        &copy; {new Date().getFullYear()} {branding.siteName}
                    </p>
                </footer>
            </div>
        </>
    );
}
