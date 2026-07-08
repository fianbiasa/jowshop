import { usePage } from '@inertiajs/react';
import LegalPageLayout from '@/components/legal-page-layout';

export default function Contact({ contactEmail }: { contactEmail: string }) {
    const { branding } = usePage().props;

    return (
        <LegalPageLayout title="Kontak">
            <p>
                Ada pertanyaan seputar pesanan atau layanan{' '}
                {branding.siteName}? Hubungi kami melalui email berikut dan
                kami akan membalas secepatnya.
            </p>

            <p>
                <a
                    href={`mailto:${contactEmail}`}
                    className="font-medium text-foreground hover:underline"
                >
                    {contactEmail}
                </a>
            </p>
        </LegalPageLayout>
    );
}
