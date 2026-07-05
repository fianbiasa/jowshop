import type { SalespageStyle as Style } from '@/types/models';

/**
 * Three curated visual styles, picked by the admin in the editor. Content
 * (block types/data) never changes between styles — only how each block is
 * themed. This config backs both the public salespage renderer
 * (pages/public/salespage-view.tsx) and the style picker's live preview
 * cards in the editor (pages/funnels/salespage-style-picker.tsx).
 *
 * This lives outside pages/ deliberately: pages/public/salespage-view.tsx is
 * lazily loaded by Inertia's page glob, and a static import from another page
 * into it breaks Vite's ability to chunk it as a standalone async page.
 */
export const PAGE_BG_CLASS: Record<Style, string> = {
    minimal: '',
    bold: 'bg-orange-50/60',
    editorial: 'bg-white',
};

export const MAIN_CLASS: Record<Style, string> = {
    minimal: 'mx-auto max-w-3xl space-y-10 px-4 py-12',
    bold: 'mx-auto max-w-3xl space-y-8 px-4 py-12',
    editorial: 'mx-auto max-w-2xl space-y-14 px-6 py-16 font-serif',
};

export const KICKER_CLASS: Record<Style, string> = {
    minimal: 'text-center text-sm font-medium text-muted-foreground',
    bold: 'text-center text-sm font-semibold tracking-wide text-amber-700 uppercase',
    editorial: 'text-center text-sm tracking-widest text-stone-400 uppercase',
};

export const HEADLINE_CLASS: Record<Style, string> = {
    minimal: 'text-center text-3xl font-bold tracking-tight sm:text-4xl',
    bold: 'text-center text-4xl font-extrabold tracking-tight text-stone-900 sm:text-5xl',
    editorial:
        'text-center text-4xl font-bold leading-tight text-stone-900 sm:text-5xl',
};

export const SUBHEADLINE_CLASS: Record<Style, string> = {
    minimal: 'text-center text-lg text-muted-foreground',
    bold: 'text-center text-lg font-medium text-stone-600',
    editorial: 'text-center text-xl italic text-stone-500',
};

export const GUARANTEE_CLASS: Record<Style, string> = {
    minimal:
        'mx-auto max-w-xl rounded-lg border border-green-200 bg-green-50 p-6 text-center text-green-800',
    bold: 'mx-auto max-w-xl rounded-xl border-2 border-green-300 bg-green-50 p-6 text-center text-base font-medium text-green-800 shadow-sm',
    editorial:
        'mx-auto max-w-xl border border-stone-200 p-6 text-center text-stone-700',
};

export const CTA_BUTTON_CLASS: Record<Style, string> = {
    minimal:
        'inline-flex items-center justify-center rounded-md bg-primary px-8 py-3 text-lg font-semibold text-primary-foreground shadow transition-colors hover:bg-primary/90',
    bold: 'inline-flex items-center justify-center gap-2 rounded-full bg-amber-500 px-10 py-4 text-lg font-bold text-white shadow-lg transition-colors hover:bg-amber-600',
    editorial:
        'inline-flex items-center justify-center rounded-none border-2 border-stone-900 px-10 py-4 text-lg font-medium text-stone-900 transition-colors hover:bg-stone-900 hover:text-white',
};
