<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import Icon from '@/components/Icon.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type QueueRow = {
    id: number;
    controlTicketNumber: string;
    natureOfRequest: string | null;
    office: string | null;
    status: string | null;
    category: string | null;
    dateFiled: string | null;
    showUrl: string;
};

defineProps<{
    queuedCountForUser: number;
    totalRequestsForUser: number;
    currentQueue: QueueRow[];
    activeQueueTotal: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User Dashboard',
        href: dashboard().url,
    },
];

const badgeBase =
    'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium leading-4';

const badgeTones = {
    emerald:
        'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    blue: 'border-blue-500/30 bg-blue-500/10 text-blue-700 dark:text-blue-300',
    amber:
        'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300',
    rose: 'border-rose-500/30 bg-rose-500/10 text-rose-700 dark:text-rose-300',
    slate:
        'border-slate-500/20 bg-slate-500/10 text-slate-600 dark:text-slate-300',
};

function resolveStatusBadge(status: string | null): string {
    const n = (status ?? '').toLowerCase();
    if (n.includes('completed') || n.includes('closed')) return badgeTones.emerald;
    if (n.includes('ongoing') || n.includes('assigned')) return badgeTones.blue;
    if (n.includes('pending') || n.includes('review')) return badgeTones.amber;
    return badgeTones.slate;
}

function resolveCategoryBadge(category: string | null): string {
    const n = (category ?? '').toLowerCase();
    if (n.includes('simple')) return badgeTones.emerald;
    if (n.includes('average') || n.includes('moderate')) return badgeTones.amber;
    if (n.includes('complex') || n.includes('urgent')) return badgeTones.rose;
    return badgeTones.slate;
}

function formatDate(value: string | null): string {
    if (!value) return '—';
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return value;
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    }).format(parsed);
}

function displayText(value: string | null, fallback = '—'): string {
    return value && value.trim().length > 0 ? value : fallback;
}
</script>

<template>
    <Head title="User Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
            <!-- Stat cards -->
            <div class="grid gap-4 sm:grid-cols-2">
                <div
                    class="flex items-start gap-4 rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-white/10"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <Icon name="clipboardList" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground"
                        >
                            Queued tickets
                        </p>
                        <p class="mt-1 text-2xl font-semibold tabular-nums text-foreground">
                            {{ queuedCountForUser }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Pending or ongoing (your requests)
                        </p>
                    </div>
                </div>
                <div
                    class="flex items-start gap-4 rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-white/10"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <Icon name="layers" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground"
                        >
                            Total requests
                        </p>
                        <p class="mt-1 text-2xl font-semibold tabular-nums text-foreground">
                            {{ totalRequestsForUser }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            All-time requests you have filed
                        </p>
                    </div>
                </div>
            </div>

            <!-- Current Queue (read-only) -->
            <div class="flex flex-col gap-3">
                <h2 class="text-sm font-semibold text-foreground">
                    Current Queue
                </h2>
                <p class="text-xs text-muted-foreground">
                    Latest 15 active requests (pending and ongoing), oldest first by date filed.
                </p>
                <div
                    class="overflow-hidden rounded-xl border border-sidebar-border/60 bg-card shadow-sm dark:border-white/10"
                >
                    <div class="max-h-[420px] overflow-auto">
                        <table class="w-full min-w-[720px] text-xs">
                            <thead
                                class="sticky top-0 z-10 border-b border-sidebar-border/60 bg-muted/50 text-[11px] uppercase tracking-wide text-muted-foreground dark:border-white/10 dark:bg-white/5"
                            >
                                <tr>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        Control Ticket No.
                                    </th>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        Nature of Request
                                    </th>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        Office
                                    </th>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        Status
                                    </th>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        Category
                                    </th>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        Date Filed
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-sidebar-border/60 text-foreground dark:divide-white/10"
                            >
                                <tr
                                    v-for="row in currentQueue"
                                    :key="row.id"
                                    class="hover:bg-muted/30 dark:hover:bg-white/5"
                                >
                                    <td class="px-3 py-2 font-medium">
                                        <Link
                                            v-if="row.showUrl"
                                            :href="row.showUrl"
                                            class="text-primary hover:underline"
                                        >
                                            {{ row.controlTicketNumber }}
                                        </Link>
                                        <span v-else>{{ row.controlTicketNumber }}</span>
                                    </td>
                                    <td class="max-w-[180px] truncate px-3 py-2" :title="displayText(row.natureOfRequest)">
                                        {{ displayText(row.natureOfRequest, '—') }}
                                    </td>
                                    <td class="max-w-[120px] truncate px-3 py-2" :title="displayText(row.office)">
                                        {{ displayText(row.office) }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <span
                                            :class="[badgeBase, resolveStatusBadge(row.status)]"
                                        >
                                            {{ displayText(row.status, '—') }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span
                                            :class="[badgeBase, resolveCategoryBadge(row.category)]"
                                        >
                                            {{ displayText(row.category, '—') }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2 text-muted-foreground">
                                        {{ formatDate(row.dateFiled) }}
                                    </td>
                                </tr>
                                <tr v-if="currentQueue.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-4 py-8 text-center text-xs text-muted-foreground"
                                    >
                                        No active requests in the queue.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div
                        v-if="activeQueueTotal > 15"
                        class="border-t border-sidebar-border/60 px-3 py-2 text-center dark:border-white/10"
                    >
                        <Link
                            href="/requests"
                            class="text-xs font-medium text-primary hover:underline"
                        >
                            View all {{ activeQueueTotal }} active requests →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
