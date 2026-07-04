import { Form, Head } from '@inertiajs/react';
import PaymentSettingController from '@/actions/App/Http/Controllers/Settings/PaymentSettingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { edit } from '@/routes/payment-settings';
import type { PaymentSettingSummary } from '@/types/models';

export default function PaymentSettings({
    paymentSetting,
}: {
    paymentSetting: PaymentSettingSummary | null;
}) {
    return (
        <>
            <Head title="Payment Settings" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Payment — Duitku"
                    description="Hubungkan akun Duitku untuk memproses pembayaran checkout"
                />

                {paymentSetting && (
                    <div className="flex items-center gap-2 rounded-lg border p-4">
                        <span className="text-sm text-muted-foreground">
                            Status:
                        </span>
                        <Badge
                            variant={
                                paymentSetting.is_active
                                    ? 'default'
                                    : 'secondary'
                            }
                        >
                            {paymentSetting.is_active ? 'Aktif' : 'Nonaktif'}
                        </Badge>
                        <Badge variant="outline" className="capitalize">
                            {paymentSetting.environment}
                        </Badge>
                    </div>
                )}

                <Form
                    {...PaymentSettingController.update.form()}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="merchant_code">
                                    Merchant Code
                                </Label>
                                <Input
                                    id="merchant_code"
                                    name="merchant_code"
                                    required
                                    placeholder="DXXXX"
                                />
                                <InputError message={errors.merchant_code} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="api_key">API Key</Label>
                                <Input
                                    id="api_key"
                                    name="api_key"
                                    type="password"
                                    required
                                    autoComplete="off"
                                />
                                <InputError message={errors.api_key} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="environment">Environment</Label>
                                <Select
                                    name="environment"
                                    defaultValue={
                                        paymentSetting?.environment ?? 'sandbox'
                                    }
                                >
                                    <SelectTrigger
                                        id="environment"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="sandbox">
                                            Sandbox
                                        </SelectItem>
                                        <SelectItem value="production">
                                            Production
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.environment} />
                            </div>

                            <input type="hidden" name="is_active" value="1" />

                            <p className="text-xs text-muted-foreground">
                                Masukkan ulang Merchant Code &amp; API Key
                                setiap kali menyimpan perubahan (kredensial
                                tidak pernah ditampilkan kembali setelah
                                tersimpan).
                            </p>

                            <Button disabled={processing}>
                                Simpan Kredensial
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

PaymentSettings.layout = {
    breadcrumbs: [{ title: 'Payment', href: edit() }],
};
