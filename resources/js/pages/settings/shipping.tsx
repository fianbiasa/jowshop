import { Form, Head } from '@inertiajs/react';
import ShippingSettingController from '@/actions/App/Http/Controllers/Settings/ShippingSettingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
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
                                        <SelectItem value="biteship">
                                            Biteship
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

                            <div className="space-y-4 rounded-lg border p-4">
                                <div>
                                    <p className="text-sm font-medium">
                                        Kontak & Alamat Asal (Biteship)
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Dipakai untuk booking pengiriman
                                        otomatis ke Biteship saat pesanan
                                        dibayar. Wajib diisi kalau "Booking
                                        Otomatis" di bawah diaktifkan.
                                    </p>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="origin_contact_name">
                                        Nama Kontak
                                    </Label>
                                    <Input
                                        id="origin_contact_name"
                                        name="origin_contact_name"
                                        defaultValue={
                                            shippingSetting?.origin_contact_name ??
                                            ''
                                        }
                                        placeholder="Budi Santoso"
                                    />
                                    <InputError
                                        message={errors.origin_contact_name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="origin_contact_phone">
                                        Nomor HP
                                    </Label>
                                    <Input
                                        id="origin_contact_phone"
                                        name="origin_contact_phone"
                                        defaultValue={
                                            shippingSetting?.origin_contact_phone ??
                                            ''
                                        }
                                        placeholder="081234567890"
                                    />
                                    <InputError
                                        message={errors.origin_contact_phone}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="origin_address">
                                        Alamat Lengkap
                                    </Label>
                                    <Textarea
                                        id="origin_address"
                                        name="origin_address"
                                        defaultValue={
                                            shippingSetting?.origin_address ??
                                            ''
                                        }
                                        placeholder="Jl. Contoh No. 1, RT/RW..."
                                    />
                                    <InputError
                                        message={errors.origin_address}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="origin_postal_code">
                                        Kode Pos
                                    </Label>
                                    <Input
                                        id="origin_postal_code"
                                        name="origin_postal_code"
                                        defaultValue={
                                            shippingSetting?.origin_postal_code ??
                                            ''
                                        }
                                        placeholder="28125"
                                    />
                                    <InputError
                                        message={errors.origin_postal_code}
                                    />
                                </div>

                                <div className="flex items-center space-x-3">
                                    <Checkbox
                                        id="auto_book_shipping"
                                        name="auto_book_shipping"
                                        value="1"
                                        defaultChecked={
                                            shippingSetting?.auto_book_shipping ??
                                            false
                                        }
                                    />
                                    <Label htmlFor="auto_book_shipping">
                                        Booking Otomatis (buat pesanan
                                        pengiriman nyata ke Biteship saat
                                        pesanan dibayar)
                                    </Label>
                                </div>
                                <InputError
                                    message={errors.auto_book_shipping}
                                />
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
