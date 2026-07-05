import { Head, Link } from '@inertiajs/react';
import MetaPixel from '@/components/meta-pixel';
import type { MetaPixelEvent } from '@/components/meta-pixel';
import { show as showCheckout } from '@/routes/public/checkout';

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

function BlockRenderer({
    block,
    funnelSlug,
}: {
    block: Block;
    funnelSlug: string;
}) {
    switch (block.type) {
        case 'headline':
            return (
                <h1 className="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                    {(block.data as { text: string }).text}
                </h1>
            );
        case 'subheadline':
            return (
                <p className="text-center text-lg text-muted-foreground">
                    {(block.data as { text: string }).text}
                </p>
            );
        case 'benefit_list': {
            const items = (block.data as { items: string[] }).items ?? [];

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
        case 'testimonial': {
            const { name, quote } = block.data as {
                name: string;
                quote: string;
            };

            return (
                <blockquote className="mx-auto max-w-xl rounded-lg border bg-muted/30 p-6 text-center italic">
                    "{quote}"
                    <footer className="mt-3 text-sm font-medium text-muted-foreground not-italic">
                        — {name}
                    </footer>
                </blockquote>
            );
        }
        case 'faq': {
            const items =
                (
                    block.data as {
                        items: { question: string; answer: string }[];
                    }
                ).items ?? [];

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
        case 'guarantee':
            return (
                <div className="mx-auto max-w-xl rounded-lg border border-green-200 bg-green-50 p-6 text-center text-green-800">
                    {(block.data as { text: string }).text}
                </div>
            );
        case 'cta':
            return (
                <div className="text-center">
                    <Link
                        href={showCheckout(funnelSlug)}
                        className="inline-flex items-center justify-center rounded-md bg-primary px-8 py-3 text-lg font-semibold text-primary-foreground shadow transition-colors hover:bg-primary/90"
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
        seo_title: string | null;
        seo_description: string | null;
    };
    product: { name: string; price: string };
    metaPixel: MetaPixelEvent | null;
}) {
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

            <main className="mx-auto max-w-3xl space-y-10 px-4 py-12">
                <div className="text-center text-sm font-medium text-muted-foreground">
                    {product.name} · {formatPrice(product.price)}
                </div>

                {salespage.content.map((block, i) => (
                    <BlockRenderer
                        key={i}
                        block={block}
                        funnelSlug={funnel.slug}
                    />
                ))}
            </main>
        </>
    );
}
