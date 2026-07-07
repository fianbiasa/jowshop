import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import BrandingSettingController from '@/actions/App/Http/Controllers/Settings/BrandingSettingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/branding-settings';

export default function BrandingSettings({
    logoUrl,
}: {
    logoUrl: string | null;
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
            </div>
        </>
    );
}

BrandingSettings.layout = {
    breadcrumbs: [{ title: 'Branding', href: edit() }],
};
