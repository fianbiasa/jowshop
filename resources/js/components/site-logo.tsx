import { usePage } from '@inertiajs/react';
import { LOGO_IMG_CLASS, LOGO_TEXT_CLASS } from '@/lib/salespage-themes';
import type { SalespageStyle as Style } from '@/types/models';

/**
 * Just the logo mark itself (image + fallback site name) — deliberately
 * unopinionated about outer spacing/alignment, since call sites embed it
 * in very different contexts: standalone atop a themed page (paired with
 * LOGO_WRAPPER_CLASS[style]) or inline inside an existing header/nav row
 * (e.g. welcome.tsx) that already controls its own layout.
 */
export default function SiteLogo({ style }: { style: Style }) {
    const { branding } = usePage().props;

    return (
        <span className="inline-flex items-center gap-2">
            <img
                src={branding.logoUrl ?? '/favicon.svg'}
                alt={branding.siteName}
                className={LOGO_IMG_CLASS[style]}
            />
            {!branding.logoUrl && (
                <span className={LOGO_TEXT_CLASS[style]}>
                    {branding.siteName}
                </span>
            )}
        </span>
    );
}
