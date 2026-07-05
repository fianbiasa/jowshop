import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
import { index } from '@/routes/funnels';
import { generate, update } from '@/routes/funnels/salespage';
import type {
    AiProviderSetting,
    Funnel,
    Salespage,
    SalespageStyle,
} from '@/types/models';
import SalespageStylePicker from './salespage-style-picker';

type BlockDataValue =
    | string
    | string[]
    | Array<{ question: string; answer: string }>
    | File
    | null;
type BlockData = Record<string, BlockDataValue>;
type Block = { type: string; data: BlockData };

const blockLabels: Record<string, string> = {
    headline: 'Headline',
    subheadline: 'Subheadline',
    benefit_list: 'Daftar Benefit',
    testimonial: 'Testimoni',
    faq: 'FAQ',
    guarantee: 'Garansi',
    cta: 'Call to Action',
    image: 'Gambar',
    video: 'Video',
    divider: 'Divider',
    spacer: 'Spacer',
};

function defaultDataForType(type: string): BlockData {
    switch (type) {
        case 'headline':
        case 'subheadline':
        case 'guarantee':
            return { text: '' };
        case 'benefit_list':
            return { items: [] };
        case 'testimonial':
            return { name: '', quote: '' };
        case 'faq':
            return { items: [{ question: '', answer: '' }] };
        case 'cta':
            return { label: '' };
        case 'image':
            return { url: '', file: null, aspect_ratio: null, alt: '' };
        case 'video':
            return {
                source: 'youtube',
                url: '',
                file: null,
                aspect_ratio: '16:9',
            };
        case 'spacer':
            return { height: 'md' };
        default:
            return {};
    }
}

function AspectRatioSelect({
    value,
    onChange,
}: {
    value: string | null;
    onChange: (value: string | null) => void;
}) {
    return (
        <Select
            value={value ?? 'auto'}
            onValueChange={(next) => onChange(next === 'auto' ? null : next)}
        >
            <SelectTrigger className="w-48">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="auto">Rasio Asli</SelectItem>
                <SelectItem value="16:9">16:9 (Landscape)</SelectItem>
                <SelectItem value="9:16">9:16 (Portrait)</SelectItem>
            </SelectContent>
        </Select>
    );
}

function BlockEditor({
    block,
    onChange,
}: {
    block: Block;
    onChange: (data: BlockData) => void;
}) {
    switch (block.type) {
        case 'headline':
        case 'subheadline':
        case 'guarantee':
            return (
                <Input
                    value={(block.data.text as string) ?? ''}
                    onChange={(e) => onChange({ text: e.target.value })}
                    placeholder="Teks..."
                />
            );
        case 'cta':
            return (
                <Input
                    value={(block.data.label as string) ?? ''}
                    onChange={(e) => onChange({ label: e.target.value })}
                    placeholder="Label tombol, mis. 'Beli Sekarang'"
                />
            );
        case 'benefit_list': {
            const items = (block.data.items as string[]) ?? [];

            return (
                <Textarea
                    value={items.join('\n')}
                    onChange={(e) =>
                        onChange({
                            items: e.target.value
                                .split('\n')
                                .filter((line) => line.trim() !== ''),
                        })
                    }
                    placeholder={'Satu benefit per baris'}
                    rows={4}
                />
            );
        }
        case 'testimonial':
            return (
                <div className="space-y-2">
                    <Input
                        value={(block.data.name as string) ?? ''}
                        onChange={(e) =>
                            onChange({ ...block.data, name: e.target.value })
                        }
                        placeholder="Nama"
                    />
                    <Textarea
                        value={(block.data.quote as string) ?? ''}
                        onChange={(e) =>
                            onChange({ ...block.data, quote: e.target.value })
                        }
                        placeholder="Kutipan testimoni"
                    />
                </div>
            );
        case 'faq': {
            const items =
                (block.data.items as { question: string; answer: string }[]) ??
                [];

            return (
                <div className="space-y-3">
                    {items.map((item, i) => (
                        <div key={i} className="space-y-2 rounded border p-2">
                            <Input
                                value={item.question}
                                onChange={(e) => {
                                    const next = [...items];
                                    next[i] = {
                                        ...item,
                                        question: e.target.value,
                                    };
                                    onChange({ items: next });
                                }}
                                placeholder="Pertanyaan"
                            />
                            <Textarea
                                value={item.answer}
                                onChange={(e) => {
                                    const next = [...items];
                                    next[i] = {
                                        ...item,
                                        answer: e.target.value,
                                    };
                                    onChange({ items: next });
                                }}
                                placeholder="Jawaban"
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() =>
                                    onChange({
                                        items: items.filter(
                                            (_, idx) => idx !== i,
                                        ),
                                    })
                                }
                            >
                                Hapus Pertanyaan
                            </Button>
                        </div>
                    ))}
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            onChange({
                                items: [...items, { question: '', answer: '' }],
                            })
                        }
                    >
                        + Tambah Pertanyaan
                    </Button>
                </div>
            );
        }
        case 'image':
            return (
                <div className="space-y-2">
                    <Input
                        type="file"
                        accept="image/*"
                        onChange={(e) =>
                            onChange({
                                ...block.data,
                                file: e.target.files?.[0] ?? null,
                            })
                        }
                    />
                    <Input
                        value={(block.data.url as string) ?? ''}
                        onChange={(e) =>
                            onChange({
                                ...block.data,
                                url: e.target.value,
                                file: null,
                            })
                        }
                        placeholder="...atau paste URL gambar"
                    />
                    <Input
                        value={(block.data.alt as string) ?? ''}
                        onChange={(e) =>
                            onChange({ ...block.data, alt: e.target.value })
                        }
                        placeholder="Teks alternatif (alt)"
                    />
                    <AspectRatioSelect
                        value={(block.data.aspect_ratio as string) ?? null}
                        onChange={(value) =>
                            onChange({ ...block.data, aspect_ratio: value })
                        }
                    />
                </div>
            );
        case 'video': {
            const source = (block.data.source as string) ?? 'youtube';

            return (
                <div className="space-y-2">
                    <Select
                        value={source}
                        onValueChange={(value) =>
                            onChange({ ...block.data, source: value })
                        }
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="youtube">
                                Link YouTube
                            </SelectItem>
                            <SelectItem value="vimeo">Link Vimeo</SelectItem>
                            <SelectItem value="file">Upload File</SelectItem>
                        </SelectContent>
                    </Select>

                    {source === 'file' ? (
                        <Input
                            type="file"
                            accept="video/*"
                            onChange={(e) =>
                                onChange({
                                    ...block.data,
                                    file: e.target.files?.[0] ?? null,
                                })
                            }
                        />
                    ) : (
                        <Input
                            value={(block.data.url as string) ?? ''}
                            onChange={(e) =>
                                onChange({
                                    ...block.data,
                                    url: e.target.value,
                                })
                            }
                            placeholder="https://youtube.com/watch?v=..."
                        />
                    )}

                    <AspectRatioSelect
                        value={(block.data.aspect_ratio as string) ?? '16:9'}
                        onChange={(value) =>
                            onChange({
                                ...block.data,
                                aspect_ratio: value ?? '16:9',
                            })
                        }
                    />
                </div>
            );
        }
        case 'divider':
            return (
                <p className="text-sm text-muted-foreground">
                    Tidak perlu pengaturan — garis pemisah tampil otomatis.
                </p>
            );
        case 'spacer':
            return (
                <Select
                    value={(block.data.height as string) ?? 'md'}
                    onValueChange={(value) => onChange({ height: value })}
                >
                    <SelectTrigger className="w-48">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="sm">Kecil</SelectItem>
                        <SelectItem value="md">Sedang</SelectItem>
                        <SelectItem value="lg">Besar</SelectItem>
                    </SelectContent>
                </Select>
            );
        default:
            return null;
    }
}

export default function SalespageEditor({
    funnel,
    salespage,
    aiProviderSettings,
}: {
    funnel: Funnel;
    salespage: Salespage | null;
    aiProviderSettings: AiProviderSetting[];
}) {
    const form = useForm<{
        title: string;
        content: Block[];
        style: SalespageStyle;
        seo_title: string;
        seo_description: string;
        is_published: boolean;
    }>({
        title: salespage?.title ?? funnel.name,
        content: (salespage?.content as Block[]) ?? [],
        style: salespage?.style ?? 'minimal',
        seo_title: salespage?.seo_title ?? '',
        seo_description: salespage?.seo_description ?? '',
        is_published: !!salespage?.published_at,
    });

    const [newBlockType, setNewBlockType] = useState('headline');
    const [aiProviderId, setAiProviderId] = useState(
        aiProviderSettings[0]?.id ? String(aiProviderSettings[0].id) : '',
    );
    const [brief, setBrief] = useState('');

    function updateBlock(index: number, data: BlockData) {
        const next = [...form.data.content];
        next[index] = { ...next[index], data };
        form.setData('content', next);
    }

    function removeBlock(index: number) {
        form.setData(
            'content',
            form.data.content.filter((_, i) => i !== index),
        );
    }

    function moveBlock(index: number, direction: -1 | 1) {
        const next = [...form.data.content];
        const target = index + direction;

        if (target < 0 || target >= next.length) {
            return;
        }

        [next[index], next[target]] = [next[target], next[index]];
        form.setData('content', next);
    }

    function addBlock() {
        form.setData('content', [
            ...form.data.content,
            { type: newBlockType, data: defaultDataForType(newBlockType) },
        ]);
    }

    function submitGenerate() {
        router.post(
            generate(funnel.id).url,
            {
                ai_provider_setting_id: aiProviderId,
                style: form.data.style,
                brief,
            },
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title={`Salespage — ${funnel.name}`} />

            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title={`Salespage: ${funnel.name}`}
                        description="Susun konten salespage secara manual atau generate dengan AI"
                    />

                    <Dialog>
                        <DialogTrigger asChild>
                            <Button
                                variant="outline"
                                disabled={aiProviderSettings.length === 0}
                            >
                                Generate dengan AI
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>
                                Generate Salespage dengan AI
                            </DialogTitle>
                            <div className="space-y-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="ai-provider">
                                        Provider AI
                                    </Label>
                                    <Select
                                        value={aiProviderId}
                                        onValueChange={setAiProviderId}
                                    >
                                        <SelectTrigger id="ai-provider">
                                            <SelectValue placeholder="Pilih provider" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {aiProviderSettings.map((s) => (
                                                <SelectItem
                                                    key={s.id}
                                                    value={String(s.id)}
                                                >
                                                    {s.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="brief">
                                        Brief Tambahan (opsional)
                                    </Label>
                                    <Textarea
                                        id="brief"
                                        value={brief}
                                        onChange={(e) =>
                                            setBrief(e.target.value)
                                        }
                                        placeholder="Target audiens, tone, benefit yang ingin ditonjolkan..."
                                    />
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Ini akan menimpa seluruh konten salespage
                                    saat ini dengan hasil generate AI.
                                </p>
                            </div>
                            <DialogFooter>
                                <Button
                                    onClick={submitGenerate}
                                    disabled={!aiProviderId}
                                >
                                    Generate
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>

                <div className="space-y-3">
                    <Label>Style Salespage</Label>
                    <SalespageStylePicker
                        value={form.data.style}
                        onChange={(style) => form.setData('style', style)}
                    />
                </div>

                <div className="space-y-4 rounded-lg border p-4">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Judul Salespage</Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="seo_title">SEO Title</Label>
                            <Input
                                id="seo_title"
                                value={form.data.seo_title}
                                onChange={(e) =>
                                    form.setData('seo_title', e.target.value)
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="seo_description">
                                SEO Description
                            </Label>
                            <Input
                                id="seo_description"
                                value={form.data.seo_description}
                                onChange={(e) =>
                                    form.setData(
                                        'seo_description',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="is_published"
                            checked={form.data.is_published}
                            onCheckedChange={(checked) =>
                                form.setData('is_published', checked === true)
                            }
                        />
                        <Label htmlFor="is_published">
                            Publikasikan salespage ini
                        </Label>
                    </div>
                </div>

                <div className="space-y-4">
                    {form.data.content.map((block, i) => (
                        <div
                            key={i}
                            className="space-y-2 rounded-lg border p-4"
                        >
                            <div className="flex items-center justify-between">
                                <span className="font-medium">
                                    {blockLabels[block.type] ?? block.type}
                                </span>
                                <div className="flex gap-1">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => moveBlock(i, -1)}
                                    >
                                        ↑
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => moveBlock(i, 1)}
                                    >
                                        ↓
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        size="sm"
                                        onClick={() => removeBlock(i)}
                                    >
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                            <BlockEditor
                                block={block}
                                onChange={(data) => updateBlock(i, data)}
                            />
                        </div>
                    ))}

                    {form.data.content.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            Belum ada blok konten. Tambahkan blok atau generate
                            dengan AI.
                        </p>
                    )}

                    <div className="flex items-center gap-2">
                        <Select
                            value={newBlockType}
                            onValueChange={setNewBlockType}
                        >
                            <SelectTrigger className="w-56">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(blockLabels).map(
                                    ([type, label]) => (
                                        <SelectItem key={type} value={type}>
                                            {label}
                                        </SelectItem>
                                    ),
                                )}
                            </SelectContent>
                        </Select>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={addBlock}
                        >
                            + Tambah Blok
                        </Button>
                    </div>
                </div>

                <Button
                    disabled={form.processing}
                    onClick={() =>
                        form.put(update(funnel.id).url, {
                            preserveScroll: true,
                        })
                    }
                >
                    Simpan Salespage
                </Button>
            </div>
        </>
    );
}

SalespageEditor.layout = {
    breadcrumbs: [
        { title: 'Funnel', href: index() },
        { title: 'Salespage', href: index() },
    ],
};
