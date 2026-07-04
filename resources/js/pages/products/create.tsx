import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { create, index } from '@/routes/products';
import ProductFormFields from './product-form-fields';

export default function Create() {
    const [type, setType] = useState<'digital' | 'physical'>('digital');

    return (
        <>
            <Head title="Tambah Produk" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Tambah Produk"
                    description="Buat produk digital atau fisik baru"
                />

                <Card>
                    <CardContent className="lg:px-10 lg:py-4">
                        <Form
                            {...ProductController.store.form()}
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <ProductFormFields
                                        errors={errors}
                                        type={type}
                                        onTypeChange={setType}
                                    />

                                    <div className="flex items-center gap-4">
                                        <Button disabled={processing}>
                                            Simpan
                                        </Button>
                                        <Button variant="secondary" asChild>
                                            <Link href={index()}>Batal</Link>
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
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
