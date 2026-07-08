import type { Auth } from '@/types/auth';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            branding: {
                logoUrl: string | null;
                siteName: string;
                address: string | null;
                email: string | null;
                phone: string | null;
            };
            [key: string]: unknown;
        };
    }
}
