import { Head } from '@inertiajs/react';
import SiteLogo from '@/components/site-logo';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { LOGO_WRAPPER_CLASS } from '@/lib/salespage-themes';

type Asset = {
    id: number;
    name: string | null;
};

export default function DigitalDownload({
    download_token: token,
    order_number: orderNumber,
    product_name: productName,
    license_key: licenseKey,
    download_count: downloadCount,
    max_downloads: maxDownloads,
    expires_at: expiresAt,
    is_expired: isExpired,
    has_reached_limit: hasReachedLimit,
    assets,
}: {
    download_token: string;
    order_number: string;
    product_name: string;
    license_key: string | null;
    download_count: number;
    max_downloads: number | null;
    expires_at: string | null;
    is_expired: boolean;
    has_reached_limit: boolean;
    assets: Asset[];
}) {
    const blocked = isExpired || hasReachedLimit;

    return (
        <>
            <Head title={`Unduh — ${productName}`} />

            <main className="mx-auto max-w-xl space-y-6 px-4 py-12">
                <div className={LOGO_WRAPPER_CLASS.minimal}>
                    <SiteLogo style="minimal" />
                </div>

                <div>
                    <h1 className="text-2xl font-bold">{productName}</h1>
                    <p className="text-sm text-muted-foreground">
                        Pesanan {orderNumber}
                    </p>
                </div>

                {isExpired && (
                    <Alert variant="destructive">
                        <AlertTitle>Tautan kedaluwarsa</AlertTitle>
                        <AlertDescription>
                            Tautan unduhan ini sudah tidak berlaku. Gunakan
                            halaman Cek Pesanan untuk meminta akses baru.
                        </AlertDescription>
                    </Alert>
                )}

                {!isExpired && hasReachedLimit && (
                    <Alert variant="destructive">
                        <AlertTitle>Batas unduhan tercapai</AlertTitle>
                        <AlertDescription>
                            File ini sudah diunduh sebanyak {downloadCount} dari
                            maksimal {maxDownloads} kali.
                        </AlertDescription>
                    </Alert>
                )}

                {licenseKey && (
                    <div className="rounded-lg border p-4">
                        <p className="text-sm font-medium">Kode Lisensi</p>
                        <p className="font-mono text-lg">{licenseKey}</p>
                    </div>
                )}

                <div className="space-y-3">
                    {assets.map((asset) => (
                        <div
                            key={asset.id}
                            className="flex items-center justify-between rounded-lg border p-4"
                        >
                            <span className="truncate text-sm">
                                {asset.name}
                            </span>
                            <Button asChild disabled={blocked}>
                                <a href={`/unduh/${token}/${asset.id}`}>
                                    Unduh
                                </a>
                            </Button>
                        </div>
                    ))}
                </div>

                {maxDownloads !== null && !blocked && (
                    <p className="text-center text-xs text-muted-foreground">
                        {downloadCount} dari {maxDownloads} unduhan terpakai.
                    </p>
                )}

                {expiresAt && (
                    <p className="text-center text-xs text-muted-foreground">
                        Tautan ini berlaku hingga{' '}
                        {new Date(expiresAt).toLocaleDateString('id-ID')}.
                    </p>
                )}
            </main>
        </>
    );
}
