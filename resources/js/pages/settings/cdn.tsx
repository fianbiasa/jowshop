import { Form, Head } from '@inertiajs/react';
import CdnSettingController from '@/actions/App/Http/Controllers/Settings/CdnSettingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/cdn-settings';
import type { CdnSettingSummary } from '@/types/models';

export default function CdnSettings({
    cdnSetting,
}: {
    cdnSetting: CdnSettingSummary | null;
}) {
    return (
        <>
            <Head title="CDN" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="CDN"
                    description="Arahkan gambar produk, logo, dan file CSS/JS lewat Bunny Pull Zone supaya lebih cepat dan meringankan server"
                />

                {cdnSetting && (
                    <div className="flex items-center gap-2 rounded-lg border p-4">
                        <span className="text-sm text-muted-foreground">
                            Status:
                        </span>
                        <Badge
                            variant={
                                cdnSetting.is_active ? 'default' : 'secondary'
                            }
                        >
                            {cdnSetting.is_active ? 'Aktif' : 'Nonaktif'}
                        </Badge>
                    </div>
                )}

                <Form
                    {...CdnSettingController.update.form()}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="pull_zone_url">
                                    Pull Zone URL
                                </Label>
                                <Input
                                    id="pull_zone_url"
                                    name="pull_zone_url"
                                    defaultValue={
                                        cdnSetting?.pull_zone_url ?? ''
                                    }
                                    placeholder="https://namazona.b-cdn.net"
                                />
                                <InputError message={errors.pull_zone_url} />
                                <p className="text-xs text-muted-foreground">
                                    Buat Pull Zone di Bunny.net dengan origin
                                    diarahkan ke domain website ini, lalu
                                    tempel URL Pull Zone-nya di sini (contoh:
                                    https://namazona.b-cdn.net). Tidak perlu
                                    upload file manual — Bunny otomatis
                                    mengambil &amp; menyimpan salinan
                                    gambar/CSS/JS dari server ini saat pertama
                                    kali diakses.
                                </p>
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="is_active"
                                    name="is_active"
                                    value="1"
                                    defaultChecked={
                                        cdnSetting?.is_active ?? false
                                    }
                                />
                                <Label htmlFor="is_active">
                                    Aktifkan CDN
                                </Label>
                            </div>
                            <InputError message={errors.is_active} />

                            <Button disabled={processing}>Simpan</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CdnSettings.layout = {
    breadcrumbs: [{ title: 'CDN', href: edit() }],
};
