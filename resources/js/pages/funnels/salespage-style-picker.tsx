import {
    CTA_BUTTON_CLASS,
    HEADLINE_CLASS,
    PAGE_BG_CLASS,
    SUBHEADLINE_CLASS,
} from '@/lib/salespage-themes';
import type { SalespageStyle as Style } from '@/types/models';

const STYLE_OPTIONS: { value: Style; label: string; description: string }[] = [
    {
        value: 'minimal',
        label: 'Minimal',
        description: 'Bersih & profesional, cocok untuk produk apa saja.',
    },
    {
        value: 'bold',
        label: 'Bold / Urgency',
        description:
            'Mencolok dengan aksen oranye, cocok untuk promo terbatas.',
    },
    {
        value: 'editorial',
        label: 'Editorial',
        description: 'Naratif & tenang, cocok untuk kelas/ecourse panjang.',
    },
];

function StylePreview({ style }: { style: Style }) {
    return (
        <div
            className={`relative h-28 w-full overflow-hidden rounded-md border ${PAGE_BG_CLASS[style] || 'bg-white'}`}
        >
            <div className="absolute top-1/2 left-1/2 w-[240px] origin-center -translate-x-1/2 -translate-y-1/2 scale-[0.38] space-y-2 text-center">
                <div className={HEADLINE_CLASS[style]}>Judul Menarik Anda</div>
                <div className={SUBHEADLINE_CLASS[style]}>
                    Sub judul pendukung di sini
                </div>
                <div>
                    <span className={CTA_BUTTON_CLASS[style]}>
                        Beli Sekarang
                    </span>
                </div>
            </div>
        </div>
    );
}

export default function SalespageStylePicker({
    value,
    onChange,
}: {
    value: Style;
    onChange: (style: Style) => void;
}) {
    return (
        <div className="grid gap-3 sm:grid-cols-3">
            {STYLE_OPTIONS.map((option) => {
                const isSelected = value === option.value;

                return (
                    <label
                        key={option.value}
                        className={`flex cursor-pointer flex-col gap-2 rounded-lg border p-3 hover:bg-muted ${isSelected ? 'border-primary' : ''}`}
                    >
                        <span className="flex items-center gap-2">
                            <input
                                type="radio"
                                name="salespage_style"
                                checked={isSelected}
                                onChange={() => onChange(option.value)}
                            />
                            <span className="font-medium">{option.label}</span>
                        </span>
                        <StylePreview style={option.value} />
                        <span className="text-xs text-muted-foreground">
                            {option.description}
                        </span>
                    </label>
                );
            })}
        </div>
    );
}
