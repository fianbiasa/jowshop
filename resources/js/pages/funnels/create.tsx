import { Form, Head, Link } from '@inertiajs/react';
import FunnelController from '@/actions/App/Http/Controllers/FunnelController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { create, index } from '@/routes/funnels';
import type { Product } from '@/types/models';

export default function Create({ products }: { products: Product[] }) {
    return (
        <>
            <Head title="Tambah Funnel" />

            <div className="mx-auto max-w-2xl space-y-6 p-4">
                <Heading
                    title="Tambah Funnel"
                    description="Buat funnel penjualan baru untuk sebuah produk"
                />

                <Form {...FunnelController.store.form()} className="space-y-6">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama Funnel</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    placeholder="Funnel Kopi Robusta"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="slug">Slug</Label>
                                <Input
                                    id="slug"
                                    name="slug"
                                    required
                                    placeholder="kopi-robusta"
                                />
                                <InputError message={errors.slug} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="product_id">Produk Utama</Label>
                                <Select name="product_id">
                                    <SelectTrigger
                                        id="product_id"
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
                                                {product.name} ({product.type})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.product_id} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button disabled={processing}>Simpan</Button>
                                <Button variant="secondary" asChild>
                                    <Link href={index()}>Batal</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Funnel', href: index() },
        { title: 'Tambah Funnel', href: create() },
    ],
};
