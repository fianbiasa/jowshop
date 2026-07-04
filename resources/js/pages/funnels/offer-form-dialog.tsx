import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import FunnelOfferController from '@/actions/App/Http/Controllers/FunnelOfferController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
import type {
    DiscountType,
    FunnelOffer,
    OfferStage,
    OfferTriggerCondition,
    Product,
} from '@/types/models';

export default function OfferFormDialog({
    trigger,
    title,
    funnelId,
    products,
    offer,
    parentOfferId = null,
    triggerCondition = 'initial',
    defaultStage,
    nextSequence,
}: {
    trigger: ReactNode;
    title: string;
    funnelId: number;
    products: Product[];
    offer?: FunnelOffer;
    parentOfferId?: number | null;
    triggerCondition?: OfferTriggerCondition;
    defaultStage: OfferStage;
    nextSequence: number;
}) {
    const formProps = offer
        ? FunnelOfferController.update.form([funnelId, offer.id])
        : FunnelOfferController.store.form(funnelId);

    return (
        <Dialog>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>

                <Form {...formProps} className="space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <input
                                type="hidden"
                                name="parent_offer_id"
                                value={
                                    offer
                                        ? (offer.parent_offer_id ?? '')
                                        : (parentOfferId ?? '')
                                }
                            />
                            <input
                                type="hidden"
                                name="trigger_condition"
                                value={
                                    offer
                                        ? offer.trigger_condition
                                        : triggerCondition
                                }
                            />
                            <input
                                type="hidden"
                                name="sequence"
                                value={offer?.sequence ?? nextSequence}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor={`stage-${offer?.id ?? 'new'}`}>
                                    Tahap
                                </Label>
                                <Select
                                    name="stage"
                                    defaultValue={offer?.stage ?? defaultStage}
                                >
                                    <SelectTrigger
                                        id={`stage-${offer?.id ?? 'new'}`}
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="bump">
                                            Order Bump
                                        </SelectItem>
                                        <SelectItem value="upsell">
                                            Upsell
                                        </SelectItem>
                                        <SelectItem value="downsell">
                                            Downsell
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.stage} />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`product-${offer?.id ?? 'new'}`}
                                >
                                    Produk yang Ditawarkan
                                </Label>
                                <Select
                                    name="product_id"
                                    defaultValue={
                                        offer
                                            ? String(offer.product_id)
                                            : undefined
                                    }
                                >
                                    <SelectTrigger
                                        id={`product-${offer?.id ?? 'new'}`}
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Pilih produk" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {products.map((product) => (
                                            <SelectItem
                                                key={product.id}
                                                value={String(product.id)}
                                            >
                                                {product.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.product_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`headline-${offer?.id ?? 'new'}`}
                                >
                                    Headline
                                </Label>
                                <Input
                                    id={`headline-${offer?.id ?? 'new'}`}
                                    name="headline"
                                    required
                                    defaultValue={offer?.headline}
                                    placeholder="Tambah Gula Aren?"
                                />
                                <InputError message={errors.headline} />
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`description-${offer?.id ?? 'new'}`}
                                >
                                    Deskripsi
                                </Label>
                                <Textarea
                                    id={`description-${offer?.id ?? 'new'}`}
                                    name="description"
                                    defaultValue={offer?.description ?? ''}
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor={`price-${offer?.id ?? 'new'}`}>
                                    Harga Override (Rp, opsional)
                                </Label>
                                <Input
                                    id={`price-${offer?.id ?? 'new'}`}
                                    name="price_override"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    defaultValue={offer?.price_override ?? ''}
                                    placeholder="Kosongkan untuk pakai harga produk"
                                />
                                <InputError message={errors.price_override} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`discount-type-${offer?.id ?? 'new'}`}
                                    >
                                        Diskon
                                    </Label>
                                    <Select
                                        name="discount_type"
                                        defaultValue={
                                            (offer?.discount_type as DiscountType) ??
                                            'none'
                                        }
                                    >
                                        <SelectTrigger
                                            id={`discount-type-${offer?.id ?? 'new'}`}
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">
                                                Tanpa diskon
                                            </SelectItem>
                                            <SelectItem value="percentage">
                                                Persentase
                                            </SelectItem>
                                            <SelectItem value="fixed">
                                                Nominal Tetap
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.discount_type}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor={`discount-value-${offer?.id ?? 'new'}`}
                                    >
                                        Nilai Diskon
                                    </Label>
                                    <Input
                                        id={`discount-value-${offer?.id ?? 'new'}`}
                                        name="discount_value"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        defaultValue={
                                            offer?.discount_value ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.discount_value}
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    type="hidden"
                                    name="is_active"
                                    value="0"
                                />
                                <Checkbox
                                    id={`active-${offer?.id ?? 'new'}`}
                                    name="is_active"
                                    value="1"
                                    defaultChecked={offer?.is_active ?? true}
                                />
                                <Label htmlFor={`active-${offer?.id ?? 'new'}`}>
                                    Aktif
                                </Label>
                                <InputError message={errors.is_active} />
                            </div>

                            <DialogFooter>
                                <Button disabled={processing} type="submit">
                                    Simpan
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
