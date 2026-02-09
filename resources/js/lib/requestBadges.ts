/**
 * Consistent color-coded badge classes for request Status and Category.
 * Readable in both light and dark mode.
 */

export const requestBadgeBase =
    'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium leading-4';

const tones = {
    amber: 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300',
    blue: 'border-blue-500/30 bg-blue-500/10 text-blue-700 dark:text-blue-300',
    emerald: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    rose: 'border-rose-500/30 bg-rose-500/10 text-rose-700 dark:text-rose-300',
    purple: 'border-purple-500/30 bg-purple-500/10 text-purple-700 dark:text-purple-300',
    slate: 'border-slate-500/20 bg-slate-500/10 text-slate-600 dark:text-slate-300',
} as const;

function normalize(value: string | null | undefined): string {
    return (value ?? '').trim().toLowerCase();
}

/**
 * Badge tone for Status (e.g. Pending, Ongoing, Completed).
 * Distinct color per value, consistent across list and detail views.
 */
export function getStatusBadgeClass(status: string | null | undefined): string {
    const n = normalize(status);
    if (n.includes('completed') || n.includes('closed')) return tones.emerald;
    if (n.includes('ongoing') || n.includes('assigned')) return tones.blue;
    if (n.includes('pending') || n.includes('review')) return tones.amber;
    return tones.slate;
}

/**
 * Badge tone for Category (e.g. Simple, Complex, Urgent).
 * Distinct color per value, consistent across list and detail views.
 */
export function getCategoryBadgeClass(category: string | null | undefined): string {
    const n = normalize(category);
    if (n.includes('simple')) return tones.emerald;
    if (n.includes('complex')) return tones.purple;
    if (n.includes('urgent')) return tones.rose;
    if (n.includes('average') || n.includes('moderate')) return tones.amber;
    return tones.slate;
}
