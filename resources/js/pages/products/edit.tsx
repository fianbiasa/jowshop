import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { index } from '@/routes/products';
import type { Product } from '@/types/models';
import ProductDigitalAssets from './product-digital-assets';
import ProductFormFields from './product-form-fields';

export default function Edit({ product }: { product: Product }) {
    const [type, setType] = useState<'digital' | 'physical'>(product.type);

    return (
        <>
            <Head title={`Edit ${product.name}`} />

            <div className="space-y-6 p-4">
                <Heading
                    title="Edit Produk"
                    description="Perbarui detail produk"
                />

                <Card>
                    <CardContent className="lg:px-10 lg:py-4">
                        <Form
                            {...ProductController.update.form(product.id)}
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <ProductFormFields
                                        product={product}
                                        errors={errors}
                                        type={type}
                                        onTypeChange={setType}
                                    />

                                    <div className="flex items-center gap-4">
                                        <Button disabled={processing}>
                                            Simpan Perubahan
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

                {type === 'digital' && (
                    <ProductDigitalAssets product={product} />
                )}
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Produk', href: index() },
        { title: 'Edit Produk', href: index() },
    ],
};
