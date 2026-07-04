import { Form } from '@inertiajs/react';
import FunnelOfferController from '@/actions/App/Http/Controllers/FunnelOfferController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { FunnelOffer, OfferStage, Product } from '@/types/models';
import OfferFormDialog from './offer-form-dialog';

const conditionLabel: Record<string, string> = {
    initial: '',
    accepted: 'Jika Diterima',
    declined: 'Jika Ditolak',
};

function OfferNode({
    offer,
    offers,
    funnelId,
    products,
}: {
    offer: FunnelOffer;
    offers: FunnelOffer[];
    funnelId: number;
    products: Product[];
}) {
    const children = offers.filter((o) => o.parent_offer_id === offer.id);
    const acceptedChild = children.find(
        (o) => o.trigger_condition === 'accepted',
    );
    const declinedChild = children.find(
        (o) => o.trigger_condition === 'declined',
    );
    const product = products.find((p) => p.id === offer.product_id);
    const nextSequence = children.length;

    return (
        <div className="space-y-3">
            <div className="rounded-lg border p-4">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        {offer.trigger_condition !== 'initial' && (
                            <Badge variant="outline" className="mb-2">
                                {conditionLabel[offer.trigger_condition]}
                            </Badge>
                        )}
                        <div className="font-medium">{offer.headline}</div>
                        <div className="text-sm text-muted-foreground">
                            {product?.name ?? 'Produk tidak ditemukan'}
                            {offer.price_override &&
                                ` · Rp${Number(offer.price_override).toLocaleString('id-ID')}`}
                        </div>
                        {!offer.is_active && (
                            <Badge variant="secondary" className="mt-2">
                                Nonaktif
                            </Badge>
                        )}
                    </div>

                    <div className="flex shrink-0 gap-2">
                        <OfferFormDialog
                            trigger={
                                <Button variant="secondary" size="sm">
                                    Edit
                                </Button>
                            }
                            title="Edit Offer"
                            funnelId={funnelId}
                            products={products}
                            offer={offer}
                            defaultStage={offer.stage}
                            nextSequence={offer.sequence}
                        />

                        <Dialog>
                            <DialogTrigger asChild>
                                <Button variant="destructive" size="sm">
                                    Hapus
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogTitle>
                                    Hapus "{offer.headline}"?
                                </DialogTitle>
                                <DialogDescription>
                                    Offer lanjutan (jika diterima/ditolak) di
                                    bawahnya akan ikut terhapus.
                                </DialogDescription>
                                <Form
                                    {...FunnelOfferController.destroy.form([
                                        funnelId,
                                        offer.id,
                                    ])}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button variant="secondary">
                                                    Batal
                                                </Button>
                                            </DialogClose>
                                            <Button
                                                variant="destructive"
                                                disabled={processing}
                                                asChild
                                            >
                                                <button type="submit">
                                                    Hapus
                                                </button>
                                            </Button>
                                        </DialogFooter>
                                    )}
                                </Form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                <div className="mt-3 flex gap-2">
                    {!acceptedChild && (
                        <OfferFormDialog
                            trigger={
                                <Button variant="outline" size="sm">
                                    + Jika Diterima
                                </Button>
                            }
                            title={`Offer Baru — Jika "${offer.headline}" Diterima`}
                            funnelId={funnelId}
                            products={products}
                            parentOfferId={offer.id}
                            triggerCondition="accepted"
                            defaultStage={offer.stage}
                            nextSequence={nextSequence}
                        />
                    )}
                    {!declinedChild && (
                        <OfferFormDialog
                            trigger={
                                <Button variant="outline" size="sm">
                                    + Jika Ditolak
                                </Button>
                            }
                            title={`Offer Baru — Jika "${offer.headline}" Ditolak`}
                            funnelId={funnelId}
                            products={products}
                            parentOfferId={offer.id}
                            triggerCondition="declined"
                            defaultStage={
                                offer.stage === 'upsell'
                                    ? 'downsell'
                                    : offer.stage
                            }
                            nextSequence={nextSequence}
                        />
                    )}
                </div>
            </div>

            {children.length > 0 && (
                <div className="ml-6 space-y-3 border-l pl-6">
                    {children.map((child) => (
                        <OfferNode
                            key={child.id}
                            offer={child}
                            offers={offers}
                            funnelId={funnelId}
                            products={products}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

export default function OfferTree({
    offers,
    rootStage,
    funnelId,
    products,
    addLabel,
}: {
    offers: FunnelOffer[];
    rootStage: OfferStage;
    funnelId: number;
    products: Product[];
    addLabel: string;
}) {
    const roots = offers.filter(
        (o) => o.parent_offer_id === null && o.stage === rootStage,
    );

    return (
        <div className="space-y-4">
            {roots.length === 0 && (
                <p className="text-sm text-muted-foreground">
                    Belum ada offer di tahap ini.
                </p>
            )}

            {roots.map((offer) => (
                <OfferNode
                    key={offer.id}
                    offer={offer}
                    offers={offers}
                    funnelId={funnelId}
                    products={products}
                />
            ))}

            <OfferFormDialog
                trigger={<Button variant="outline">{addLabel}</Button>}
                title={addLabel}
                funnelId={funnelId}
                products={products}
                triggerCondition="initial"
                defaultStage={rootStage}
                nextSequence={roots.length}
            />
        </div>
    );
}
