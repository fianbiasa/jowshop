import { Form, Head } from '@inertiajs/react';
import AiProviderSettingController from '@/actions/App/Http/Controllers/Settings/AiProviderSettingController';
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
import { index } from '@/routes/ai-providers';
import type { AiProviderSetting } from '@/types/models';

export default function AiProviders({
    aiProviderSettings,
}: {
    aiProviderSettings: AiProviderSetting[];
}) {
    return (
        <>
            <Head title="AI Providers" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="AI Providers"
                    description="Hubungkan API key AI milikmu sendiri (OpenAI, Anthropic, Gemini) untuk fitur Salespage Generator"
                />

                {aiProviderSettings.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Belum ada provider AI ditambahkan.
                    </p>
                ) : (
                    <div className="space-y-3">
                        {aiProviderSettings.map((setting) => (
                            <div
                                key={setting.id}
                                className="flex items-center justify-between rounded-lg border p-4"
                            >
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="font-medium">
                                            {setting.label}
                                        </span>
                                        {setting.is_default && (
                                            <Badge>Default</Badge>
                                        )}
                                        {!setting.is_active && (
                                            <Badge variant="secondary">
                                                Nonaktif
                                            </Badge>
                                        )}
                                    </div>
                                    <div className="text-sm text-muted-foreground capitalize">
                                        {setting.provider} ·{' '}
                                        {setting.default_model}
                                    </div>
                                </div>

                                <Form
                                    {...AiProviderSettingController.destroy.form(
                                        setting.id,
                                    )}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            disabled={processing}
                                            type="submit"
                                        >
                                            Hapus
                                        </Button>
                                    )}
                                </Form>
                            </div>
                        ))}
                    </div>
                )}

                <div className="rounded-lg border p-4">
                    <Heading variant="small" title="Tambah Provider Baru" />

                    <Form
                        {...AiProviderSettingController.store.form()}
                        resetOnSuccess
                        options={{ preserveScroll: true }}
                        className="mt-4 space-y-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="provider">Provider</Label>
                                    <Select
                                        name="provider"
                                        defaultValue="openai"
                                    >
                                        <SelectTrigger
                                            id="provider"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="openai">
                                                OpenAI
                                            </SelectItem>
                                            <SelectItem value="anthropic">
                                                Anthropic
                                            </SelectItem>
                                            <SelectItem value="gemini">
                                                Google Gemini
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.provider} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="label">Nama Tampilan</Label>
                                    <Input
                                        id="label"
                                        name="label"
                                        required
                                        placeholder="OpenAI Utama"
                                    />
                                    <InputError message={errors.label} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="api_key">API Key</Label>
                                    <Input
                                        id="api_key"
                                        name="api_key"
                                        type="password"
                                        required
                                        autoComplete="off"
                                        placeholder="sk-..."
                                    />
                                    <InputError message={errors.api_key} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="default_model">
                                        Model Default
                                    </Label>
                                    <Input
                                        id="default_model"
                                        name="default_model"
                                        required
                                        placeholder="gpt-4.1"
                                    />
                                    <InputError
                                        message={errors.default_model}
                                    />
                                </div>

                                <input
                                    type="hidden"
                                    name="is_default"
                                    value="1"
                                />
                                <input
                                    type="hidden"
                                    name="is_active"
                                    value="1"
                                />

                                <Button disabled={processing}>
                                    Tambah Provider
                                </Button>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

AiProviders.layout = {
    breadcrumbs: [{ title: 'AI Providers', href: index() }],
};
