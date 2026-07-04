import { router, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import ProductDigitalAssetController from '@/actions/App/Http/Controllers/ProductDigitalAssetController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Product, ProductDigitalAsset } from '@/types/models';

export default function ProductDigitalAssets({
    product,
}: {
    product: Product;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        file: File | null;
        external_url: string;
        license_type: string;
        max_downloads: string;
    }>({
        file: null,
        external_url: '',
        license_type: 'none',
        max_downloads: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(ProductDigitalAssetController.store.url(product.id), {
            forceFormData: true,
            onSuccess: () => reset(),
        });
    };

    const remove = (asset: ProductDigitalAsset) => {
        if (!confirm('Hapus file digital ini?')) {
            return;
        }

        router.delete(
            ProductDigitalAssetController.destroy.url({
                product: product.id,
                digitalAsset: asset.id,
            }),
        );
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>File Digital</CardTitle>
                <CardDescription>
                    Unggah file yang akan dikirimkan ke pembeli setelah
                    pembayaran berhasil. Bisa berupa unggahan file atau tautan
                    eksternal.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {product.digital_assets &&
                    product.digital_assets.length > 0 && (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Sumber</TableHead>
                                    <TableHead>Lisensi</TableHead>
                                    <TableHead>Maks. Unduhan</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {product.digital_assets.map((asset) => (
                                    <TableRow key={asset.id}>
                                        <TableCell>
                                            {asset.file_path
                                                ? asset.file_path
                                                      .split('/')
                                                      .pop()
                                                : asset.external_url}
                                        </TableCell>
                                        <TableCell>
                                            {asset.license_type}
                                        </TableCell>
                                        <TableCell>
                                            {asset.max_downloads ??
                                                'Tanpa batas'}
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => remove(asset)}
                                            >
                                                Hapus
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}

                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="file">Unggah File</Label>
                        <Input
                            id="file"
                            type="file"
                            onChange={(e) =>
                                setData('file', e.target.files?.[0] ?? null)
                            }
                        />
                        <InputError message={errors.file} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="external_url">
                            Atau Tautan Eksternal
                        </Label>
                        <Input
                            id="external_url"
                            value={data.external_url}
                            onChange={(e) =>
                                setData('external_url', e.target.value)
                            }
                            placeholder="https://drive.google.com/..."
                        />
                        <InputError message={errors.external_url} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="license_type">Tipe Lisensi</Label>
                        <Select
                            value={data.license_type}
                            onValueChange={(value) =>
                                setData('license_type', value)
                            }
                        >
                            <SelectTrigger id="license_type" className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    Tanpa Lisensi
                                </SelectItem>
                                <SelectItem value="license_key">
                                    Kode Lisensi Otomatis
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.license_type} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="max_downloads">
                            Maks. Unduhan (kosongkan jika tanpa batas)
                        </Label>
                        <Input
                            id="max_downloads"
                            type="number"
                            min="1"
                            value={data.max_downloads}
                            onChange={(e) =>
                                setData('max_downloads', e.target.value)
                            }
                        />
                        <InputError message={errors.max_downloads} />
                    </div>

                    <Button type="submit" disabled={processing}>
                        Tambah File
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}
