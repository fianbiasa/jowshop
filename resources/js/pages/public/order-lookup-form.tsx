import { Form, Head } from '@inertiajs/react';
import OrderLookupController from '@/actions/App/Http/Controllers/OrderLookupController';
import InputError from '@/components/input-error';
import SiteLogo from '@/components/site-logo';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { LOGO_WRAPPER_CLASS } from '@/lib/salespage-themes';

export default function OrderLookupForm() {
    return (
        <>
            <Head title="Cek Pesanan" />

            <main className="mx-auto max-w-md space-y-6 px-4 py-12">
                <div className={LOGO_WRAPPER_CLASS.minimal}>
                    <SiteLogo style="minimal" />
                </div>

                <div>
                    <h1 className="text-2xl font-bold">Cek Pesanan</h1>
                    <p className="text-sm text-muted-foreground">
                        Masukkan email dan nomor pesananmu untuk melihat status
                        pesanan dan mengunduh ulang file digital.
                    </p>
                </div>

                <Form
                    {...OrderLookupController.store.form()}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                    placeholder="budi@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="order_number">
                                    Nomor Pesanan
                                </Label>
                                <Input
                                    id="order_number"
                                    name="order_number"
                                    required
                                    placeholder="ORD-XXXXXXXX"
                                />
                                <InputError message={errors.order_number} />
                            </div>

                            <Button
                                type="submit"
                                disabled={processing}
                                className="w-full"
                            >
                                Cek Pesanan
                            </Button>
                        </>
                    )}
                </Form>
            </main>
        </>
    );
}
