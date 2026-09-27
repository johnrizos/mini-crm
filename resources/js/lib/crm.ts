import type { ActivityType, ContactStatus, DealStage } from '@/types/crm';

export const contactStatuses: { value: ContactStatus; label: string }[] = [
    { value: 'lead', label: 'Lead' },
    { value: 'prospect', label: 'Prospect' },
    { value: 'customer', label: 'Customer' },
    { value: 'churned', label: 'Churned' },
];

export const dealStages: {
    value: DealStage;
    label: string;
    closed: boolean;
}[] = [
    { value: 'new', label: 'New', closed: false },
    { value: 'qualified', label: 'Qualified', closed: false },
    { value: 'proposal', label: 'Proposal', closed: false },
    { value: 'negotiation', label: 'Negotiation', closed: false },
    { value: 'won', label: 'Won', closed: true },
    { value: 'lost', label: 'Lost', closed: true },
];

export const activityTypes: { value: ActivityType; label: string }[] = [
    { value: 'note', label: 'Note' },
    { value: 'call', label: 'Call' },
    { value: 'email', label: 'Email' },
    { value: 'meeting', label: 'Meeting' },
];

export const statusClasses: Record<ContactStatus, string> = {
    lead: 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
    prospect:
        'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    customer:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    churned:
        'bg-neutral-200 text-neutral-700 dark:bg-neutral-500/20 dark:text-neutral-300',
};

export const stageAccent: Record<DealStage, string> = {
    new: 'bg-sky-500',
    qualified: 'bg-indigo-500',
    proposal: 'bg-violet-500',
    negotiation: 'bg-amber-500',
    won: 'bg-emerald-500',
    lost: 'bg-neutral-400',
};

export function label<T extends string>(
    list: { value: T; label: string }[],
    value: T,
): string {
    return list.find((item) => item.value === value)?.label ?? value;
}

const euros = new Intl.NumberFormat('en-IE', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
});

export function money(cents: number): string {
    return euros.format(cents / 100);
}

export function formatDate(iso: string | null): string {
    if (!iso) {
        return '';
    }

    return new Date(iso).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

const relative = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });

export function timeAgo(iso: string): string {
    const seconds = (new Date(iso).getTime() - Date.now()) / 1000;
    const steps: [Intl.RelativeTimeFormatUnit, number][] = [
        ['year', 31536000],
        ['month', 2592000],
        ['week', 604800],
        ['day', 86400],
        ['hour', 3600],
        ['minute', 60],
    ];

    for (const [unit, size] of steps) {
        if (Math.abs(seconds) >= size) {
            return relative.format(Math.round(seconds / size), unit);
        }
    }

    return 'just now';
}
