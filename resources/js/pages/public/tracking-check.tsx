import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/tracking';

type Tracking = {
    status: string;
    history: { status: string; note: string; updated_at: string }[];
};

export default function TrackingCheck({
    tracking,
    error,
}: {
    tracking?: Tracking;
    error?: string;
}) {
    return (
        <>
            <Head title="Cek Resi" />

            <main className="mx-auto max-w-lg space-y-6 px-4 py-12">
                <div>
                    <h1 className="text-2xl font-bold">Cek Resi</h1>
                    <p className="text-sm text-muted-foreground">
                        Masukkan kode kurir dan nomor resi untuk melihat status
                        pengiriman terkini.
                    </p>
                </div>

                <Form {...store.form()} className="space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="courier">Kurir</Label>
                                <Input
                                    id="courier"
                                    name="courier"
                                    required
                                    placeholder="jne, jnt, sicepat, anteraja, dll"
                                />
                                <InputError message={errors.courier} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tracking_number">
                                    Nomor Resi
                                </Label>
                                <Input
                                    id="tracking_number"
                                    name="tracking_number"
                                    required
                                    placeholder="020170030469926"
                                />
                                <InputError message={errors.tracking_number} />
                            </div>

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                Cek Resi
                            </Button>
                        </>
                    )}
                </Form>

                {error && (
                    <p className="text-center text-sm text-destructive">
                        {error}
                    </p>
                )}

                {tracking && (
                    <div className="space-y-2 rounded-lg border bg-muted/30 p-4 text-sm">
                        <p className="font-medium capitalize">
                            Status: {tracking.status}
                        </p>
                        <ul className="space-y-1">
                            {tracking.history.map((entry) => (
                                <li
                                    key={`${entry.status}-${entry.updated_at}`}
                                    className="text-muted-foreground"
                                >
                                    <span className="font-medium text-foreground capitalize">
                                        {entry.status}
                                    </span>{' '}
                                    — {entry.note} ({entry.updated_at})
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </main>
        </>
    );
}
