import { usePage } from '@inertiajs/react';
import LegalPageLayout from '@/components/legal-page-layout';

export default function Contact() {
    const { branding } = usePage().props;

    return (
        <LegalPageLayout title="Kontak">
            <p>
                Ada pertanyaan seputar pesanan atau layanan{' '}
                {branding.siteName}? Hubungi kami melalui kontak berikut dan
                kami akan membalas secepatnya.
            </p>

            <dl className="space-y-3">
                {branding.email && (
                    <div>
                        <dt className="text-xs text-muted-foreground/70">
                            Email
                        </dt>
                        <dd>
                            <a
                                href={`mailto:${branding.email}`}
                                className="font-medium text-foreground hover:underline"
                            >
                                {branding.email}
                            </a>
                        </dd>
                    </div>
                )}

                {branding.phone && (
                    <div>
                        <dt className="text-xs text-muted-foreground/70">
                            Telepon / WhatsApp
                        </dt>
                        <dd>
                            <a
                                href={`tel:${branding.phone}`}
                                className="font-medium text-foreground hover:underline"
                            >
                                {branding.phone}
                            </a>
                        </dd>
                    </div>
                )}

                {branding.address && (
                    <div>
                        <dt className="text-xs text-muted-foreground/70">
                            Alamat
                        </dt>
                        <dd className="font-medium text-foreground whitespace-pre-line">
                            {branding.address}
                        </dd>
                    </div>
                )}
            </dl>
        </LegalPageLayout>
    );
}
