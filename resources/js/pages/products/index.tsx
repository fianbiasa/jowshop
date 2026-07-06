import { Form, Head, Link } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import Heading from '@/components/heading';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { create, edit, index } from '@/routes/products';
import type { Paginated, Product } from '@/types/models';

const statusVariant: Record<
    Product['status'],
    'default' | 'secondary' | 'outline'
> = {
    draft: 'outline',
    published: 'default',
    archived: 'secondary',
};

const priceFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

const formatPrice = (price: string) => priceFormatter.format(Number(price));

export default function Index({ products }: { products: Paginated<Product> }) {
    return (
        <>
            <Head title="Produk" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Produk"
                        description="Kelola produk digital dan fisik"
                    />
                    <Button asChild>
                        <Link href={create()}>Tambah Produk</Link>
                    </Button>
                </div>

                {products.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Belum ada produk. Klik "Tambah Produk" untuk membuat
                        produk pertama.
                    </p>
                ) : (
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Tipe</TableHead>
                                    <TableHead>Harga</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {products.data.map((product) => (
                                    <TableRow key={product.id}>
                                        <TableCell className="font-medium">
                                            {product.name}
                                            <div className="text-xs text-muted-foreground">
                                                {product.slug}
                                            </div>
                                        </TableCell>
                                        <TableCell className="capitalize">
                                            {product.type}
                                        </TableCell>
                                        <TableCell>
                                            {formatPrice(product.price)}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariant[
                                                        product.status
                                                    ]
                                                }
                                                className="capitalize"
                                            >
                                                {product.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={edit(product.id)}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>

                                                <Dialog>
                                                    <DialogTrigger asChild>
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                        >
                                                            Hapus
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent>
                                                        <DialogTitle>
                                                            Hapus {product.name}
                                                            ?
                                                        </DialogTitle>
                                                        <DialogDescription>
                                                            Tindakan ini tidak
                                                            bisa dibatalkan.
                                                        </DialogDescription>
                                                        <Form
                                                            {...ProductController.destroy.form(
                                                                product.id,
                                                            )}
                                                            options={{
                                                                preserveScroll: true,
                                                            }}
                                                        >
                                                            {({
                                                                processing,
                                                            }) => (
                                                                <DialogFooter className="gap-2">
                                                                    <DialogClose
                                                                        asChild
                                                                    >
                                                                        <Button variant="secondary">
                                                                            Batal
                                                                        </Button>
                                                                    </DialogClose>
                                                                    <Button
                                                                        variant="destructive"
                                                                        disabled={
                                                                            processing
                                                                        }
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
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                {products.last_page > 1 && (
                    <div className="flex flex-wrap gap-2">
                        {products.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                asChild={!!link.url}
                            >
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ) : (
                                    <span
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                )}
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [{ title: 'Produk', href: index() }],
};
