import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/products';
import ProductFormFields from './product-form-fields';

export default function Create() {
    const [type, setType] = useState<'digital' | 'physical'>('digital');

    return (
        <>
            <Head title="Tambah Produk" />

            <div className="mx-auto max-w-2xl space-y-6 p-4">
                <Heading
                    title="Tambah Produk"
                    description="Buat produk digital atau fisik baru"
                />

                <Form {...ProductController.store.form()} className="space-y-6">
                    {({ processing, errors }) => (
                        <>
                            <ProductFormFields
                                errors={errors}
                                type={type}
                                onTypeChange={setType}
                            />

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
        { title: 'Produk', href: index() },
        { title: 'Tambah Produk', href: create() },
    ],
};
