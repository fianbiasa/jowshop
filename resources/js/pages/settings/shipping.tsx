import { Form, Head } from '@inertiajs/react';
import ShippingSettingController from '@/actions/App/Http/Controllers/Settings/ShippingSettingController';
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
import { edit } from '@/routes/shipping-settings';
import type { ShippingSettingSummary } from '@/types/models';

export default function ShippingSettings({
    shippingSetting,
}: {
    shippingSetting: ShippingSettingSummary | null;
}) {
    return (
        <>
            <Head title="Shipping Settings" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Shipping — RajaOngkir/Komerce"
                    description="Hubungkan akun kurir untuk kalkulasi ongkir otomatis saat checkout produk fisik"
                />

                {shippingSetting && (
                    <div className="flex items-center gap-2 rounded-lg border p-4">
                        <span className="text-sm text-muted-foreground">
                            Status:
                        </span>
                        <Badge
                            variant={
                                shippingSetting.is_active
                                    ? 'default'
                                    : 'secondary'
                            }
                        >
                            {shippingSetting.is_active ? 'Aktif' : 'Nonaktif'}
                        </Badge>
                        {shippingSetting.origin_label && (
                            <span className="text-sm text-muted-foreground">
                                Asal: {shippingSetting.origin_label}
                            </span>
                        )}
                    </div>
                )}

                <Form
                    {...ShippingSettingController.update.form()}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="provider">Provider</Label>
                                <Select
                                    name="provider"
                                    defaultValue={
                                        shippingSetting?.provider ?? 'komerce'
                                    }
                                >
                                    <SelectTrigger
                                        id="provider"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="komerce">
                                            Komerce
                                        </SelectItem>
                                        <SelectItem value="rajaongkir">
                                            RajaOngkir
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.provider} />
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
                                <Label htmlFor="origin_area_id">
                                    ID Area Asal Pengiriman
                                </Label>
                                <Input
                                    id="origin_area_id"
                                    name="origin_area_id"
                                    required
                                    defaultValue={
                                        shippingSetting?.origin_area_id ?? ''
                                    }
                                    placeholder="17549"
                                />
                                <InputError message={errors.origin_area_id} />
                                <p className="text-xs text-muted-foreground">
                                    ID kecamatan/kota asal dari database
                                    Komerce/RajaOngkir (bisa dicari lewat
                                    dashboard provider).
                                </p>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="origin_label">
                                    Label Asal (opsional)
                                </Label>
                                <Input
                                    id="origin_label"
                                    name="origin_label"
                                    defaultValue={
                                        shippingSetting?.origin_label ?? ''
                                    }
                                    placeholder="Kebayoran Baru, Jakarta Selatan, DKI Jakarta"
                                />
                                <InputError message={errors.origin_label} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="enabled_couriers">
                                    Kurir Diaktifkan
                                </Label>
                                <Input
                                    id="enabled_couriers"
                                    name="enabled_couriers"
                                    defaultValue={
                                        shippingSetting?.enabled_couriers ?? ''
                                    }
                                    placeholder="jne,jnt,sicepat"
                                />
                                <InputError message={errors.enabled_couriers} />
                                <p className="text-xs text-muted-foreground">
                                    Kode kurir dipisah koma. Kosongkan untuk
                                    menampilkan semua kurir yang didukung
                                    provider.
                                </p>
                            </div>

                            <input type="hidden" name="is_active" value="1" />

                            <p className="text-xs text-muted-foreground">
                                Masukkan ulang API Key setiap kali menyimpan
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

ShippingSettings.layout = {
    breadcrumbs: [{ title: 'Shipping', href: edit() }],
};
