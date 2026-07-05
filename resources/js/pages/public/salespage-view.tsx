import { Head, Link } from '@inertiajs/react';
import MetaPixel from '@/components/meta-pixel';
import type { MetaPixelEvent } from '@/components/meta-pixel';
import {
    CTA_BUTTON_CLASS,
    GUARANTEE_CLASS,
    HEADLINE_CLASS,
    KICKER_CLASS,
    MAIN_CLASS,
    PAGE_BG_CLASS,
    SUBHEADLINE_CLASS,
} from '@/lib/salespage-themes';
import { show as showCheckout } from '@/routes/public/checkout';
import type { SalespageStyle as Style } from '@/types/models';

type Block =
    | { type: 'headline'; data: { text: string } }
    | { type: 'subheadline'; data: { text: string } }
    | { type: 'benefit_list'; data: { items: string[] } }
    | { type: 'testimonial'; data: { name: string; quote: string } }
    | { type: 'faq'; data: { items: { question: string; answer: string }[] } }
    | { type: 'guarantee'; data: { text: string } }
    | { type: 'cta'; data: { label: string } }
    | { type: string; data: Record<string, unknown> };

function formatPrice(price: string) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(price));
}

function BenefitList({ items, style }: { items: string[]; style: Style }) {
    if (style === 'bold') {
        return (
            <div className="mx-auto flex max-w-xl flex-col items-center gap-3">
                {items.map((item, i) => (
                    <span
                        key={i}
                        className="inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-4 py-2 text-sm font-medium text-green-800"
                    >
                        <span aria-hidden>✓</span>
                        {item}
                    </span>
                ))}
            </div>
        );
    }

    if (style === 'editorial') {
        return (
            <ul className="mx-auto max-w-xl space-y-4 text-lg leading-relaxed text-stone-700">
                {items.map((item, i) => (
                    <li key={i} className="flex gap-3">
                        <span aria-hidden className="text-stone-400">
                            —
                        </span>
                        <span>{item}</span>
                    </li>
                ))}
            </ul>
        );
    }

    return (
        <ul className="mx-auto max-w-xl space-y-3">
            {items.map((item, i) => (
                <li key={i} className="flex items-start gap-2">
                    <span aria-hidden className="mt-1 text-green-600">
                        ✓
                    </span>
                    <span>{item}</span>
                </li>
            ))}
        </ul>
    );
}

function Testimonial({
    name,
    quote,
    style,
}: {
    name: string;
    quote: string;
    style: Style;
}) {
    if (style === 'bold') {
        return (
            <div className="mx-auto max-w-xl rounded-xl border-l-4 border-amber-500 bg-white p-6 shadow-sm">
                <p className="text-stone-700">"{quote}"</p>
                <p className="mt-3 text-sm font-semibold text-stone-900">
                    — {name}
                </p>
            </div>
        );
    }

    if (style === 'editorial') {
        return (
            <div className="mx-auto max-w-2xl text-center">
                <div aria-hidden className="text-6xl leading-none text-stone-200">
                    "
                </div>
                <p className="mt-2 text-2xl leading-relaxed text-stone-800 italic">
                    {quote}
                </p>
                <p className="mt-4 text-sm font-medium tracking-wide text-stone-500 uppercase">
                    {name}
                </p>
            </div>
        );
    }

    return (
        <blockquote className="mx-auto max-w-xl rounded-lg border bg-muted/30 p-6 text-center italic">
            "{quote}"
            <footer className="mt-3 text-sm font-medium text-muted-foreground not-italic">
                — {name}
            </footer>
        </blockquote>
    );
}

function Faq({
    items,
    style,
}: {
    items: { question: string; answer: string }[];
    style: Style;
}) {
    if (style === 'bold') {
        return (
            <div className="mx-auto max-w-xl space-y-3">
                {items.map((item, i) => (
                    <div
                        key={i}
                        className="rounded-lg border border-amber-100 bg-amber-50/50 p-4"
                    >
                        <div className="font-semibold text-stone-900">
                            {item.question}
                        </div>
                        <p className="mt-1 text-sm text-stone-600">
                            {item.answer}
                        </p>
                    </div>
                ))}
            </div>
        );
    }

    if (style === 'editorial') {
        return (
            <div className="mx-auto max-w-xl space-y-6">
                {items.map((item, i) => (
                    <div key={i}>
                        <div className="text-lg font-semibold text-stone-900">
                            {item.question}
                        </div>
                        <p className="mt-2 leading-relaxed text-stone-600">
                            {item.answer}
                        </p>
                    </div>
                ))}
            </div>
        );
    }

    return (
        <div className="mx-auto max-w-xl space-y-4">
            {items.map((item, i) => (
                <div key={i}>
                    <div className="font-medium">{item.question}</div>
                    <p className="text-sm text-muted-foreground">
                        {item.answer}
                    </p>
                </div>
            ))}
        </div>
    );
}

function BlockRenderer({
    block,
    funnelSlug,
    style,
}: {
    block: Block;
    funnelSlug: string;
    style: Style;
}) {
    switch (block.type) {
        case 'headline':
            return (
                <div>
                    <h1 className={HEADLINE_CLASS[style]}>
                        {(block.data as { text: string }).text}
                    </h1>
                    {style === 'editorial' && (
                        <div
                            aria-hidden
                            className="mx-auto mt-6 h-px w-16 bg-stone-300"
                        />
                    )}
                </div>
            );
        case 'subheadline':
            return (
                <p className={SUBHEADLINE_CLASS[style]}>
                    {(block.data as { text: string }).text}
                </p>
            );
        case 'benefit_list':
            return (
                <BenefitList
                    items={(block.data as { items: string[] }).items ?? []}
                    style={style}
                />
            );
        case 'testimonial': {
            const { name, quote } = block.data as {
                name: string;
                quote: string;
            };

            return <Testimonial name={name} quote={quote} style={style} />;
        }
        case 'faq':
            return (
                <Faq
                    items={
                        (
                            block.data as {
                                items: { question: string; answer: string }[];
                            }
                        ).items ?? []
                    }
                    style={style}
                />
            );
        case 'guarantee':
            return (
                <div className={GUARANTEE_CLASS[style]}>
                    {(block.data as { text: string }).text}
                </div>
            );
        case 'cta':
            return (
                <div className="text-center">
                    <Link
                        href={showCheckout(funnelSlug)}
                        className={CTA_BUTTON_CLASS[style]}
                    >
                        {(block.data as { label: string }).label}
                    </Link>
                </div>
            );
        default:
            return null;
    }
}

export default function PublicSalespage({
    funnel,
    salespage,
    product,
    metaPixel,
}: {
    funnel: { name: string; slug: string };
    salespage: {
        title: string;
        content: Block[];
        style: Style;
        seo_title: string | null;
        seo_description: string | null;
    };
    product: { name: string; price: string };
    metaPixel: MetaPixelEvent | null;
}) {
    const style = salespage.style;

    return (
        <>
            <Head title={salespage.seo_title || salespage.title}>
                {salespage.seo_description && (
                    <meta
                        name="description"
                        content={salespage.seo_description}
                    />
                )}
            </Head>

            <MetaPixel event={metaPixel} />

            <div className={`min-h-screen ${PAGE_BG_CLASS[style]}`}>
                <main className={MAIN_CLASS[style]}>
                    <div className={KICKER_CLASS[style]}>
                        {product.name} · {formatPrice(product.price)}
                    </div>

                    {salespage.content.map((block, i) => (
                        <BlockRenderer
                            key={i}
                            block={block}
                            funnelSlug={funnel.slug}
                            style={style}
                        />
                    ))}
                </main>
            </div>
        </>
    );
}
