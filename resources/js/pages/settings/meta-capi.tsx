import { Form, Head } from '@inertiajs/react';
import MetaCapiSettingController from '@/actions/App/Http/Controllers/Settings/MetaCapiSettingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/meta-capi-settings';
import type { MetaCapiSettingSummary } from '@/types/models';

export default function MetaCapiSettings({
    metaCapiSetting,
}: {
    metaCapiSetting: MetaCapiSettingSummary | null;
}) {
    return (
        <>
            <Head title="Meta Conversions API" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Meta Conversions API"
                    description="Kirim event konversi ke Meta/Facebook lewat server, melengkapi Pixel browser agar tracking tetap akurat meski diblokir ad-blocker/ITP"
                />

                {metaCapiSetting && (
                    <div className="flex items-center gap-2 rounded-lg border p-4">
                        <span className="text-sm text-muted-foreground">
                            Status:
                        </span>
                        <Badge
                            variant={
                                metaCapiSetting.is_active
                                    ? 'default'
                                    : 'secondary'
                            }
                        >
                            {metaCapiSetting.is_active ? 'Aktif' : 'Nonaktif'}
                        </Badge>
                        <Badge variant="outline">
                            Pixel {metaCapiSetting.pixel_id}
                        </Badge>
                    </div>
                )}

                <Form
                    {...MetaCapiSettingController.update.form()}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="pixel_id">Pixel ID</Label>
                                <Input
                                    id="pixel_id"
                                    name="pixel_id"
                                    required
                                    defaultValue={metaCapiSetting?.pixel_id}
                                    placeholder="1234567890123456"
                                />
                                <InputError message={errors.pixel_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="access_token">
                                    Access Token
                                </Label>
                                <Input
                                    id="access_token"
                                    name="access_token"
                                    type="password"
                                    required
                                    autoComplete="off"
                                />
                                <InputError message={errors.access_token} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="test_event_code">
                                    Test Event Code (opsional)
                                </Label>
                                <Input
                                    id="test_event_code"
                                    name="test_event_code"
                                    defaultValue={
                                        metaCapiSetting?.test_event_code ?? ''
                                    }
                                    placeholder="TEST12345"
                                />
                                <InputError message={errors.test_event_code} />
                                <p className="text-xs text-muted-foreground">
                                    Isi untuk memvalidasi event di Meta Events
                                    Manager &gt; Test Events sebelum melepas ke
                                    traffic produksi.
                                </p>
                            </div>

                            <input type="hidden" name="is_active" value="1" />

                            <p className="text-xs text-muted-foreground">
                                Masukkan ulang Access Token setiap kali
                                menyimpan perubahan (kredensial tidak pernah
                                ditampilkan kembali setelah tersimpan).
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

MetaCapiSettings.layout = {
    breadcrumbs: [{ title: 'Meta Conversions API', href: edit() }],
};
