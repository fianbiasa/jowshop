import type { ComponentType } from 'react';

/**
 * Shared between app.tsx (client, layouts lazy-loaded) and ssr.tsx (server,
 * layouts imported statically since React.lazy/Suspense isn't supported by
 * renderToString) so the two entry points can't drift out of sync.
 */
export function resolveLayout(
    name: string,
    layouts: Record<'AppLayout' | 'AuthLayout' | 'SettingsLayout', ComponentType<any>>,
) {
    switch (true) {
        case name === 'welcome':
            return null;
        case name.startsWith('public/'):
            return null;
        case name.startsWith('legal/'):
            return null;
        case name.startsWith('auth/'):
            return layouts.AuthLayout;
        case name.startsWith('settings/'):
            return [layouts.AppLayout, layouts.SettingsLayout];
        default:
            return layouts.AppLayout;
    }
}
