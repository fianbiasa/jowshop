import { usePage } from '@inertiajs/react';

export default function AppLogo() {
    const { branding } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md">
                <img
                    src={branding.logoUrl ?? '/favicon.svg'}
                    alt={branding.siteName}
                    className="size-8 object-contain"
                />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {branding.siteName}
                </span>
            </div>
        </>
    );
}
