import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import BrandingSettingController from '@/actions/App/Http/Controllers/Settings/BrandingSettingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { edit } from '@/routes/branding-settings';

export default function BrandingSettings({
    logoUrl,
    address,
    email,
    phone,
}: {
    logoUrl: string | null;
    address: string | null;
    email: string | null;
    phone: string | null;
}) {
    const [preview, setPreview] = useState<string | null>(null);

    return (
        <>
            <Head title="Branding" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Branding"
                    description="Unggah logo yang akan ditampilkan di halaman utama, funnel, dan checkout"
                />

                {(preview ?? logoUrl) && (
                    <div className="flex items-center gap-4 rounded-lg border p-4">
                        <img
                            src={preview ?? logoUrl ?? ''}
                            alt="Logo"
                            className="h-12 w-auto"
                        />
                        <span className="text-sm text-muted-foreground">
                            {preview ? 'Pratinjau logo baru' : 'Logo saat ini'}
                        </span>
                    </div>
                )}

                <Form
                    {...BrandingSettingController.update.form()}
                    resetOnSuccess
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="logo">Logo</Label>
                                <Input
                                    id="logo"
                                    name="logo"
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) => {
                                        const file = e.target.files?.[0];
                                        setPreview(
                                            file
                                                ? URL.createObjectURL(file)
                                                : null,
                                        );
                                    }}
                                />
                                <InputError message={errors.logo} />
                                <p className="text-xs text-muted-foreground">
                                    PNG atau JPG, maksimal 2MB. Kosongkan untuk
                                    mempertahankan logo saat ini.
                                </p>
                            </div>

                            <Button disabled={processing}>Simpan Logo</Button>
                        </>
                    )}
                </Form>

                {logoUrl && (
                    <Form
                        {...BrandingSettingController.update.form()}
                        className="contents"
                    >
                        {({ processing }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="remove_logo"
                                    value="1"
                                />
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                >
                                    Hapus Logo
                                </Button>
                            </>
                        )}
                    </Form>
                )}

                <Heading
                    variant="small"
                    title="Informasi Kontak"
                    description="Ditampilkan di halaman utama, kontak, dan syarat & kebijakan"
                />

                <Form
                    {...BrandingSettingController.update.form()}
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
                                    defaultValue={email ?? ''}
                                    placeholder="halo@bisnismu.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">
                                    Nomor Telepon / WhatsApp
                                </Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    defaultValue={phone ?? ''}
                                    placeholder="0812xxxxxxxx"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address">Alamat</Label>
                                <Textarea
                                    id="address"
                                    name="address"
                                    defaultValue={address ?? ''}
                                    placeholder="Jl. Contoh No. 1, Jakarta"
                                />
                                <InputError message={errors.address} />
                            </div>

                            <Button disabled={processing}>
                                Simpan Informasi Kontak
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

BrandingSettings.layout = {
    breadcrumbs: [{ title: 'Branding', href: edit() }],
};
