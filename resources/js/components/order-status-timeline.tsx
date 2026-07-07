import type { SalespageStyle as Style } from '@/types/models';

type OrderStatus =
    | 'pending'
    | 'paid'
    | 'processing'
    | 'shipped'
    | 'completed'
    | 'cancelled'
    | 'expired';

const STEPS = ['Dipesan', 'Dibayar', 'Diproses', 'Dikirim', 'Selesai'];

/** Which step is currently active for a given order status. */
const ACTIVE_STEP_INDEX: Partial<Record<OrderStatus, number>> = {
    pending: 1,
    paid: 2,
    processing: 3,
    shipped: 4,
    completed: 4,
};

type StepState = 'done' | 'active' | 'pending';

function stepState(
    stepIndex: number,
    activeIndex: number,
    isComplete: boolean,
): StepState {
    if (isComplete || stepIndex < activeIndex) {
        return 'done';
    }

    return stepIndex === activeIndex ? 'active' : 'pending';
}

const DOT_CLASS: Record<Style, Record<StepState, string>> = {
    minimal: {
        done: 'bg-primary text-primary-foreground border-primary',
        active: 'border-primary text-primary bg-background',
        pending:
            'border-muted-foreground/30 text-muted-foreground/50 bg-background',
    },
    bold: {
        done: 'bg-amber-500 text-white border-amber-500',
        active: 'border-amber-500 text-amber-600 bg-white shadow-[0_0_0_4px_#fef3c7]',
        pending: 'border-stone-300 text-stone-300 bg-white',
    },
    editorial: {
        done: 'border-stone-900 text-stone-900 bg-white',
        active: 'border-stone-900 text-stone-900 bg-white',
        pending: 'border-stone-300 text-stone-300 bg-white',
    },
    ledger: {
        done: 'bg-teal-700 text-white border-teal-700',
        active: 'border-teal-700 text-teal-700 bg-white',
        pending: 'border-stone-300 text-stone-400 bg-white border-dashed',
    },
};

const LABEL_CLASS: Record<Style, Record<StepState, string>> = {
    minimal: {
        done: 'text-foreground font-medium',
        active: 'text-primary font-semibold',
        pending: 'text-muted-foreground',
    },
    bold: {
        done: 'text-stone-900 font-semibold',
        active: 'text-amber-700 font-bold',
        pending: 'text-stone-400',
    },
    editorial: {
        done: 'text-stone-900',
        active: 'text-stone-900 font-medium',
        pending: 'text-stone-400',
    },
    ledger: {
        done: 'text-stone-900 font-mono',
        active: 'text-teal-700 font-mono font-semibold',
        pending: 'text-stone-400 font-mono',
    },
};

const LINE_CLASS: Record<Style, string> = {
    minimal: 'bg-muted-foreground/20',
    bold: 'bg-stone-200',
    editorial: 'bg-stone-200',
    ledger: 'bg-stone-300',
};

const LINE_PROGRESS_CLASS: Record<Style, string> = {
    minimal: 'bg-primary',
    bold: 'bg-amber-500',
    editorial: 'bg-stone-900',
    ledger: 'bg-teal-700',
};

export default function OrderStatusTimeline({
    status,
    style,
}: {
    status: OrderStatus;
    style: Style;
}) {
    const activeIndex = ACTIVE_STEP_INDEX[status];

    if (activeIndex === undefined) {
        return null;
    }

    const isComplete = status === 'completed';
    const progressPercent = (activeIndex / (STEPS.length - 1)) * 100;

    return (
        <div className="relative px-2 py-4" aria-label="Status pesanan">
            <div
                aria-hidden
                className={`absolute top-[35px] right-[10%] left-[10%] h-0.5 ${LINE_CLASS[style]}`}
            />
            <div
                aria-hidden
                className={`absolute top-[35px] left-[10%] h-0.5 ${LINE_PROGRESS_CLASS[style]}`}
                style={{ width: `${(progressPercent * 0.8).toFixed(1)}%` }}
            />
            <ol className="relative flex justify-between">
                {STEPS.map((label, index) => {
                    const state = stepState(index, activeIndex, isComplete);

                    return (
                        <li
                            key={label}
                            className="flex flex-1 flex-col items-center gap-2"
                        >
                            <span
                                className={`flex size-9 items-center justify-center rounded-full border-2 text-xs font-bold ${DOT_CLASS[style][state]}`}
                            >
                                {style === 'ledger' ? (
                                    index + 1
                                ) : state === 'done' ? (
                                    '✓'
                                ) : (
                                    <span aria-hidden>•</span>
                                )}
                            </span>
                            <span
                                className={`text-center text-xs ${LABEL_CLASS[style][state]}`}
                            >
                                {label}
                            </span>
                        </li>
                    );
                })}
            </ol>
        </div>
    );
}
