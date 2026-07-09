import { Form, Head } from '@inertiajs/react';
import WhatsAppSettingController from '@/actions/App/Http/Controllers/Settings/WhatsAppSettingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/whatsapp-settings';
import type { WhatsAppSettingSummary } from '@/types/models';

export default function WhatsAppSettings({
    whatsAppSetting,
}: {
    whatsAppSetting: WhatsAppSettingSummary | null;
}) {
    return (
        <>
            <Head title="Notifikasi WhatsApp" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Notifikasi WhatsApp"
                    description="Kirim semua notifikasi pesanan (konfirmasi, pembayaran, pengingat, resi, file digital) ke WhatsApp pelanggan lewat Starsender, melengkapi notifikasi email"
                />

                {whatsAppSetting && (
                    <div className="flex items-center gap-2 rounded-lg border p-4">
                        <span className="text-sm text-muted-foreground">
                            Status:
                        </span>
                        <Badge
                            variant={
                                whatsAppSetting.is_active
                                    ? 'default'
                                    : 'secondary'
                            }
                        >
                            {whatsAppSetting.is_active ? 'Aktif' : 'Nonaktif'}
                        </Badge>
                    </div>
                )}

                <Form
                    {...WhatsAppSettingController.update.form()}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="api_key">
                                    Device API Key Starsender
                                </Label>
                                <Input
                                    id="api_key"
                                    name="api_key"
                                    type="password"
                                    required
                                    autoComplete="off"
                                />
                                <InputError message={errors.api_key} />
                                <p className="text-xs text-muted-foreground">
                                    Ambil API key dari menu Device di dashboard
                                    Starsender. Pastikan perangkat WhatsApp
                                    sudah terhubung.
                                </p>
                            </div>

                            <input type="hidden" name="is_active" value="1" />

                            <p className="text-xs text-muted-foreground">
                                Masukkan ulang API key setiap kali menyimpan
                                perubahan (kredensial tidak pernah ditampilkan
                                kembali setelah tersimpan).
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

WhatsAppSettings.layout = {
    breadcrumbs: [{ title: 'Notifikasi WhatsApp', href: edit() }],
};
