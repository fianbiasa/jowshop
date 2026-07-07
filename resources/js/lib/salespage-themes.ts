import type { SalespageStyle as Style } from '@/types/models';

/**
 * Four curated visual styles, picked by the admin in the editor. Content
 * (block types/data) never changes between styles — only how each block is
 * themed. This config backs both the public salespage renderer
 * (pages/public/salespage-view.tsx) and the style picker's live preview
 * cards in the editor (pages/funnels/salespage-style-picker.tsx).
 *
 * This lives outside pages/ deliberately: pages/public/salespage-view.tsx is
 * lazily loaded by Inertia's page glob, and a static import from another page
 * into it breaks Vite's ability to chunk it as a standalone async page.
 *
 * "ledger" ("Nota"): a receipt/invoice motif — numerals and prices in
 * font-mono (Tailwind's built-in monospace stack, no font request), dashed
 * rules standing in for tear-perforations, and a hard offset shadow on the
 * CTA like a stamped ticket stub. Leans on transparency-as-trust rather
 * than urgency (bold) or narrative (editorial).
 */
export const PAGE_BG_CLASS: Record<Style, string> = {
    minimal: '',
    bold: 'bg-orange-50/60',
    editorial: 'bg-white',
    ledger: 'bg-stone-50',
};

export const MAIN_CLASS: Record<Style, string> = {
    minimal: 'mx-auto max-w-3xl space-y-10 px-4 py-12',
    bold: 'mx-auto max-w-3xl space-y-8 px-4 py-12',
    editorial: 'mx-auto max-w-2xl space-y-14 px-6 py-16 font-serif',
    ledger: 'mx-auto max-w-2xl space-y-8 px-4 py-12',
};

export const KICKER_CLASS: Record<Style, string> = {
    minimal: 'text-center text-sm font-medium text-muted-foreground',
    bold: 'text-center text-sm font-semibold tracking-wide text-amber-700 uppercase',
    editorial: 'text-center text-sm tracking-widest text-stone-400 uppercase',
    ledger: 'text-center font-mono text-xs tracking-widest text-teal-700 uppercase',
};

export const HEADLINE_CLASS: Record<Style, string> = {
    minimal: 'text-center text-3xl font-bold tracking-tight sm:text-4xl',
    bold: 'text-center text-4xl font-extrabold tracking-tight text-stone-900 sm:text-5xl',
    editorial:
        'text-center text-4xl font-bold leading-tight text-stone-900 sm:text-5xl',
    ledger: 'text-center text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl',
};

export const SUBHEADLINE_CLASS: Record<Style, string> = {
    minimal: 'text-center text-lg text-muted-foreground',
    bold: 'text-center text-lg font-medium text-stone-600',
    editorial: 'text-center text-xl italic text-stone-500',
    ledger: 'text-center text-base text-stone-600',
};

/** A free-form multi-line paragraph block — left-aligned body copy, unlike the centered headline/subheadline. */
export const PARAGRAPH_CLASS: Record<Style, string> = {
    minimal: 'mx-auto max-w-xl text-base leading-relaxed whitespace-pre-line',
    bold: 'mx-auto max-w-xl text-base leading-relaxed text-stone-700 whitespace-pre-line',
    editorial:
        'mx-auto max-w-xl text-lg leading-relaxed text-stone-700 whitespace-pre-line',
    ledger: 'mx-auto max-w-xl font-mono text-sm leading-relaxed text-stone-800 whitespace-pre-line',
};

export const GUARANTEE_CLASS: Record<Style, string> = {
    minimal:
        'mx-auto max-w-xl rounded-lg border border-green-200 bg-green-50 p-6 text-center text-green-800',
    bold: 'mx-auto max-w-xl rounded-xl border-2 border-green-300 bg-green-50 p-6 text-center text-base font-medium text-green-800 shadow-sm',
    editorial:
        'mx-auto max-w-xl border border-stone-200 p-6 text-center text-stone-700',
    ledger: 'mx-auto max-w-xl rounded-none border border-dashed border-teal-300 bg-teal-50/50 p-6 text-center font-mono text-sm text-teal-900',
};

export const CTA_BUTTON_CLASS: Record<Style, string> = {
    minimal:
        'inline-flex items-center justify-center rounded-md bg-primary px-8 py-3 text-lg font-semibold text-primary-foreground shadow transition-colors hover:bg-primary/90',
    bold: 'inline-flex items-center justify-center gap-2 rounded-full bg-amber-500 px-10 py-4 text-lg font-bold text-white shadow-lg transition-colors hover:bg-amber-600',
    editorial:
        'inline-flex items-center justify-center rounded-none border-2 border-stone-900 px-10 py-4 text-lg font-medium text-stone-900 transition-colors hover:bg-stone-900 hover:text-white',
    ledger: 'inline-flex items-center justify-center gap-2 rounded-sm bg-teal-700 px-8 py-3 font-mono text-lg font-bold tracking-wide text-white shadow-[3px_3px_0_0_#1c1917] transition-all hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-[1px_1px_0_0_#1c1917]',
};

/**
 * A de-emphasized secondary action (e.g. "decline this offer") that should
 * never visually compete with CTA_BUTTON_CLASS.
 */
export const SECONDARY_BUTTON_CLASS: Record<Style, string> = {
    minimal:
        'inline-flex items-center justify-center rounded-md border px-8 py-3 text-muted-foreground transition-colors hover:bg-muted',
    bold: 'text-sm font-medium text-stone-500 underline decoration-stone-300 underline-offset-4 transition-colors hover:text-stone-700',
    editorial:
        'text-sm text-stone-500 underline decoration-stone-300 underline-offset-4 transition-colors hover:text-stone-800',
    ledger: 'font-mono text-sm text-stone-500 underline decoration-dashed underline-offset-4 transition-colors hover:text-stone-800',
};

/** A themed highlight box for a single product/price callout (bump/upsell/downsell offers). */
export const OFFER_CARD_CLASS: Record<Style, string> = {
    minimal:
        'mx-auto max-w-xl rounded-lg border bg-muted/30 p-6 text-lg font-semibold',
    bold: 'mx-auto max-w-xl rounded-xl border-2 border-amber-200 bg-amber-50 p-6 text-lg font-bold text-stone-900 shadow-sm',
    editorial:
        'mx-auto max-w-xl border border-stone-200 p-6 text-lg font-medium text-stone-900',
    ledger: 'mx-auto max-w-xl rounded-sm border border-dashed border-stone-300 bg-white p-6 font-mono text-lg font-semibold text-stone-900',
};

/** Structural, not theme-dependent — a forced crop container for image/video blocks. */
export const ASPECT_RATIO_CLASS: Record<string, string> = {
    '16:9': 'aspect-video',
    '9:16': 'aspect-[9/16]',
};

export const DIVIDER_CLASS: Record<Style, string> = {
    minimal: 'mx-auto max-w-xl border-t',
    bold: 'mx-auto max-w-xl border-t-2 border-amber-200',
    editorial: 'mx-auto max-w-xs border-t border-stone-300',
    ledger: 'mx-auto max-w-xl border-t border-dashed border-stone-300',
};

/**
 * A bordered panel for order-detail pages (checkout-return,
 * order-lookup-result) — product/shipment/payment sections. These pages
 * aren't part of the editable block content, but should still feel like
 * they belong to the funnel's chosen style.
 */
export const CARD_CLASS: Record<Style, string> = {
    minimal: 'rounded-lg border p-6',
    bold: 'rounded-xl border-2 border-amber-200 bg-amber-50/40 p-6 shadow-sm',
    editorial: 'border border-stone-200 p-6',
    ledger: 'rounded-none border border-dashed border-stone-300 bg-white p-6 font-mono',
};

/** Structural, not theme-dependent — a plain vertical gap. */
export const SPACER_HEIGHT_CLASS: Record<string, string> = {
    sm: 'h-4',
    md: 'h-8',
    lg: 'h-16',
};

/**
 * The site logo (components/site-logo.tsx), shown at the top of every
 * public page. Only size/position/companion-text vary per style — never a
 * filter/tint on the uploaded image itself, so an admin's real brand
 * colors always come through unaltered.
 */
export const LOGO_WRAPPER_CLASS: Record<Style, string> = {
    minimal: 'mb-6 flex items-center justify-center',
    bold: 'mb-6 flex items-center justify-start',
    editorial: 'mb-8 flex items-center justify-center',
    ledger: 'mb-6 flex items-center justify-start border-b border-dashed border-stone-300 pb-4',
};

export const LOGO_IMG_CLASS: Record<Style, string> = {
    minimal: 'h-8 w-auto',
    bold: 'h-10 w-auto',
    editorial: 'h-6 w-auto',
    ledger: 'h-7 w-auto',
};

export const LOGO_TEXT_CLASS: Record<Style, string> = {
    minimal: 'text-lg font-semibold',
    bold: 'text-xl font-extrabold text-stone-900',
    editorial: 'font-serif text-lg font-medium tracking-wide text-stone-700',
    ledger: 'font-mono text-base font-bold text-stone-900',
};
