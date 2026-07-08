import { Head, Link } from '@inertiajs/react';
import SiteLogo from '@/components/site-logo';
import { LOGO_WRAPPER_CLASS } from '@/lib/salespage-themes';
import { home } from '@/routes';

export default function LegalPageLayout({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <>
            <Head title={title} />

            <main className="mx-auto max-w-2xl space-y-8 px-4 py-12">
                <div className="flex items-center justify-between">
                    <div className={LOGO_WRAPPER_CLASS.minimal}>
                        <SiteLogo style="minimal" />
                    </div>
                    <Link
                        href={home()}
                        className="text-sm text-muted-foreground hover:text-foreground hover:underline"
                    >
                        Kembali ke Beranda
                    </Link>
                </div>

                <div className="space-y-6">
                    <h1 className="text-2xl font-bold">{title}</h1>
                    <div className="space-y-4 text-sm leading-relaxed text-muted-foreground [&_h2]:mt-6 [&_h2]:text-base [&_h2]:font-semibold [&_h2]:text-foreground">
                        {children}
                    </div>
                </div>
            </main>
        </>
    );
}
