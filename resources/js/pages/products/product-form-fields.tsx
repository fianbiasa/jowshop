import InputError from '@/components/input-error';
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
import type { Product } from '@/types/models';

type Errors = Partial<Record<string, string>>;

export default function ProductFormFields({
    product,
    errors,
    type,
    onTypeChange,
}: {
    product?: Product;
    errors: Errors;
    type: 'digital' | 'physical';
    onTypeChange: (type: 'digital' | 'physical') => void;
}) {
    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="name">Nama Produk</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    defaultValue={product?.name}
                    placeholder="Kopi Robusta 200gr"
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="slug">Slug</Label>
                <Input
                    id="slug"
                    name="slug"
                    required
                    defaultValue={product?.slug}
                    placeholder="kopi-robusta-200gr"
                />
                <InputError message={errors.slug} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="type">Tipe Produk</Label>
                <Select
                    name="type"
                    value={type}
                    onValueChange={(value) =>
                        onTypeChange(value as 'digital' | 'physical')
                    }
                >
                    <SelectTrigger id="type" className="w-full">
                        <SelectValue placeholder="Pilih tipe produk" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="digital">Digital</SelectItem>
                        <SelectItem value="physical">Fisik</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={errors.type} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="description">Deskripsi</Label>
                <Textarea
                    id="description"
                    name="description"
                    defaultValue={product?.description ?? ''}
                    placeholder="Deskripsi produk"
                />
                <InputError message={errors.description} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="price">Harga (Rp)</Label>
                <Input
                    id="price"
                    name="price"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    defaultValue={product?.price}
                    placeholder="50000"
                />
                <InputError message={errors.price} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="sku">SKU</Label>
                <Input
                    id="sku"
                    name="sku"
                    defaultValue={product?.sku ?? ''}
                    placeholder="SKU-0001"
                />
                <InputError message={errors.sku} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="status">Status</Label>
                <Select name="status" defaultValue={product?.status ?? 'draft'}>
                    <SelectTrigger id="status" className="w-full">
                        <SelectValue placeholder="Pilih status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="draft">Draft</SelectItem>
                        <SelectItem value="published">Published</SelectItem>
                        <SelectItem value="archived">Archived</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={errors.status} />
            </div>

            {type === 'physical' && (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="weight_grams">Berat (gram)</Label>
                        <Input
                            id="weight_grams"
                            name="weight_grams"
                            type="number"
                            min="1"
                            required
                            defaultValue={product?.weight_grams ?? ''}
                            placeholder="500"
                        />
                        <InputError message={errors.weight_grams} />
                    </div>

                    <div className="grid grid-cols-3 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="length_cm">Panjang (cm)</Label>
                            <Input
                                id="length_cm"
                                name="length_cm"
                                type="number"
                                step="0.01"
                                min="0"
                                defaultValue={product?.length_cm ?? ''}
                            />
                            <InputError message={errors.length_cm} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="width_cm">Lebar (cm)</Label>
                            <Input
                                id="width_cm"
                                name="width_cm"
                                type="number"
                                step="0.01"
                                min="0"
                                defaultValue={product?.width_cm ?? ''}
                            />
                            <InputError message={errors.width_cm} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="height_cm">Tinggi (cm)</Label>
                            <Input
                                id="height_cm"
                                name="height_cm"
                                type="number"
                                step="0.01"
                                min="0"
                                defaultValue={product?.height_cm ?? ''}
                            />
                            <InputError message={errors.height_cm} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="stock">Stok</Label>
                        <Input
                            id="stock"
                            name="stock"
                            type="number"
                            min="0"
                            required
                            defaultValue={product?.stock ?? ''}
                            placeholder="100"
                        />
                        <InputError message={errors.stock} />
                    </div>
                </>
            )}
        </>
    );
}
