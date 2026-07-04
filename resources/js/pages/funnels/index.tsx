import { Form, Head, Link } from '@inertiajs/react';
import FunnelController from '@/actions/App/Http/Controllers/FunnelController';
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
import { create, edit, index } from '@/routes/funnels';
import type { Funnel, FunnelStatus, Paginated } from '@/types/models';

const statusVariant: Record<FunnelStatus, 'default' | 'secondary' | 'outline'> =
    {
        draft: 'outline',
        published: 'default',
        archived: 'secondary',
    };

export default function Index({ funnels }: { funnels: Paginated<Funnel> }) {
    return (
        <>
            <Head title="Funnel" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Funnel"
                        description="Kelola funnel penjualan: salespage, order bump, upsell & downsell"
                    />
                    <Button asChild>
                        <Link href={create()}>Tambah Funnel</Link>
                    </Button>
                </div>

                {funnels.data.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Belum ada funnel. Klik "Tambah Funnel" untuk membuat
                        funnel pertama.
                    </p>
                ) : (
                    <div className="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama Funnel</TableHead>
                                    <TableHead>Produk Utama</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {funnels.data.map((funnel) => (
                                    <TableRow key={funnel.id}>
                                        <TableCell className="font-medium">
                                            {funnel.name}
                                            <div className="text-xs text-muted-foreground">
                                                /{funnel.slug}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {funnel.product?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariant[funnel.status]
                                                }
                                                className="capitalize"
                                            >
                                                {funnel.status}
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
                                                        href={edit(funnel.id)}
                                                    >
                                                        Kelola
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
                                                            Hapus {funnel.name}?
                                                        </DialogTitle>
                                                        <DialogDescription>
                                                            Tindakan ini akan
                                                            menghapus seluruh
                                                            salespage dan offer
                                                            di dalamnya. Tidak
                                                            bisa dibatalkan.
                                                        </DialogDescription>
                                                        <Form
                                                            {...FunnelController.destroy.form(
                                                                funnel.id,
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

                {funnels.last_page > 1 && (
                    <div className="flex flex-wrap gap-2">
                        {funnels.links.map((link, i) => (
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
    breadcrumbs: [{ title: 'Funnel', href: index() }],
};
