import { Form, Head, Link } from '@inertiajs/react';
import FunnelController from '@/actions/App/Http/Controllers/FunnelController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { index } from '@/routes/funnels';
import { edit as editSalespage } from '@/routes/funnels/salespage';
import type { Funnel, FunnelOffer, Product } from '@/types/models';
import OfferTree from './offer-tree';

export default function Edit({
    funnel,
    offers,
    products,
    publishRequirementErrors,
}: {
    funnel: Funnel;
    offers: FunnelOffer[];
    products: Product[];
    publishRequirementErrors: string[];
}) {
    return (
        <>
            <Head title={`Kelola ${funnel.name}`} />

            <div className="mx-auto max-w-3xl space-y-8 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title={funnel.name}
                        description="Kelola detail funnel, order bump, upsell & downsell"
                    />
                    <Button variant="outline" asChild>
                        <Link href={editSalespage(funnel.id)}>
                            Kelola Salespage
                        </Link>
                    </Button>
                </div>

                {funnel.status !== 'published' &&
                    publishRequirementErrors.length > 0 && (
                        <Alert variant="destructive">
                            <AlertTitle>Belum bisa dipublish</AlertTitle>
                            <AlertDescription>
                                <ul className="list-inside list-disc">
                                    {publishRequirementErrors.map((err) => (
                                        <li key={err}>{err}</li>
                                    ))}
                                </ul>
                            </AlertDescription>
                        </Alert>
                    )}

                <section className="space-y-4 rounded-lg border p-4">
                    <Heading variant="small" title="Detail Funnel" />

                    <Form
                        {...FunnelController.update.form(funnel.id)}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Nama Funnel</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        defaultValue={funnel.name}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="slug">Slug</Label>
                                    <Input
                                        id="slug"
                                        name="slug"
                                        required
                                        defaultValue={funnel.slug}
                                    />
                                    <InputError message={errors.slug} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="product_id">
                                        Produk Utama
                                    </Label>
                                    <Select
                                        name="product_id"
                                        defaultValue={String(funnel.product_id)}
                                    >
                                        <SelectTrigger
                                            id="product_id"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {products.map((product) => (
                                                <SelectItem
                                                    key={product.id}
                                                    value={String(product.id)}
                                                >
                                                    {product.name} (
                                                    {product.type})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.product_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="status">Status</Label>
                                    <Select
                                        name="status"
                                        defaultValue={funnel.status}
                                    >
                                        <SelectTrigger
                                            id="status"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="draft">
                                                Draft
                                            </SelectItem>
                                            <SelectItem value="published">
                                                Published
                                            </SelectItem>
                                            <SelectItem value="archived">
                                                Archived
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.status} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="thank_you_message">
                                        Pesan Thank You Page
                                    </Label>
                                    <Textarea
                                        id="thank_you_message"
                                        name="thank_you_message"
                                        defaultValue={
                                            funnel.thank_you_message ?? ''
                                        }
                                        placeholder="Terima kasih sudah berbelanja!"
                                    />
                                    <InputError
                                        message={errors.thank_you_message}
                                    />
                                </div>

                                <div className="space-y-4">
                                    <Heading
                                        variant="small"
                                        title="Tracking Pixel"
                                        description="Untuk sinkronisasi konversi ke platform iklan"
                                    />

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="fb_pixel_id">
                                                Facebook Pixel ID
                                            </Label>
                                            <Input
                                                id="fb_pixel_id"
                                                name="fb_pixel_id"
                                                defaultValue={
                                                    funnel.fb_pixel_id ?? ''
                                                }
                                            />
                                            <InputError
                                                message={errors.fb_pixel_id}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="tiktok_pixel_id">
                                                TikTok Pixel ID
                                            </Label>
                                            <Input
                                                id="tiktok_pixel_id"
                                                name="tiktok_pixel_id"
                                                defaultValue={
                                                    funnel.tiktok_pixel_id ?? ''
                                                }
                                            />
                                            <InputError
                                                message={errors.tiktok_pixel_id}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="ga4_id">
                                                Google Analytics 4 ID
                                            </Label>
                                            <Input
                                                id="ga4_id"
                                                name="ga4_id"
                                                defaultValue={
                                                    funnel.ga4_id ?? ''
                                                }
                                            />
                                            <InputError
                                                message={errors.ga4_id}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="google_ads_id">
                                                Google Ads ID
                                            </Label>
                                            <Input
                                                id="google_ads_id"
                                                name="google_ads_id"
                                                defaultValue={
                                                    funnel.google_ads_id ?? ''
                                                }
                                            />
                                            <InputError
                                                message={errors.google_ads_id}
                                            />
                                        </div>
                                    </div>
                                </div>

                                <Button disabled={processing}>
                                    Simpan Perubahan
                                </Button>
                            </>
                        )}
                    </Form>
                </section>

                <section className="space-y-4 rounded-lg border p-4">
                    <Heading
                        variant="small"
                        title="Order Bump"
                        description="Ditawarkan saat checkout, sebelum pembayaran ditekan"
                    />
                    <OfferTree
                        offers={offers}
                        rootStage="bump"
                        funnelId={funnel.id}
                        products={products}
                        addLabel="+ Tambah Order Bump"
                    />
                </section>

                <section className="space-y-4 rounded-lg border p-4">
                    <Heading
                        variant="small"
                        title="Upsell & Downsell"
                        description="Ditawarkan setelah pembayaran pertama berhasil"
                    />
                    <OfferTree
                        offers={offers}
                        rootStage="upsell"
                        funnelId={funnel.id}
                        products={products}
                        addLabel="+ Tambah Upsell"
                    />
                </section>
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Funnel', href: index() },
        { title: 'Kelola Funnel', href: index() },
    ],
};
