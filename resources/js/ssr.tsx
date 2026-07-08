import { createInertiaApp } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { resolveLayout } from '@/lib/resolve-layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) =>
        resolveLayout(name, { AppLayout, AuthLayout, SettingsLayout }),
});
