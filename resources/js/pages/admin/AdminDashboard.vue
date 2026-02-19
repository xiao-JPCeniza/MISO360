<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import Icon from '@/components/Icon.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

type QueueRow = {
    id: number;
    controlTicketNumber: string;
    requesterName: string | null;
    office: string | null;
    category: string | null;
    status: string | null;
    remarks: string | null;
    natureOfRequest: string | null;
    dateFiled: string | null;
    showUrl: string;
};

type ArchiveItem = QueueRow;

const props = defineProps<{
    totalGenerated: number;
    activeQueueTotal: number;
    stats: {
        activeInQueue: number;
        assignedToMe: number;
        totalReceived: number;
    };
    activeQueue: QueueRow[];
    archive: {
        data: ArchiveItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: {
        control_ticket_number: string | null;
    };
    sort: { by: string; dir: string };
    archiveSearch: string | null;
    archivePanelOpen?: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/admin/dashboard',
    },
];

const showArchive = ref(props.archivePanelOpen ?? false);

const filterForm = useForm({
    control_ticket_number: props.filters.control_ticket_number ?? '',
});

const archiveSearchForm = useForm({
    archive_search: props.archiveSearch ?? '',
});

const exportDateFrom = ref('');
const exportDateTo = ref('');

const exportArchiveUrl = computed(() => {
    const params = new URLSearchParams();
    if (archiveSearchForm.archive_search) {
        params.set('archive_search', archiveSearchForm.archive_search);
    }
    if (exportDateFrom.value) params.set('date_from', exportDateFrom.value);
    if (exportDateTo.value) params.set('date_to', exportDateTo.value);
    const qs = params.toString();
    return `/admin/dashboard/archive-export${qs ? `?${qs}` : ''}`;
});

const hasActiveFilters = computed(() => !!filterForm.control_ticket_number);

function buildQuery(overrides: Record<string, string | undefined> = {}) {
    const q: Record<string, string> = {};
    if (filterForm.control_ticket_number)
        q.control_ticket_number = filterForm.control_ticket_number;
    if (props.archiveSearch) q.archive_search = props.archiveSearch;
    return { ...q, ...overrides };
}

function applyFilters() {
    router.get('/admin/dashboard', buildQuery(), {
        preserveState: true,
        only: ['activeQueue', 'activeQueueTotal', 'stats', 'filters', 'sort', 'archive', 'archiveSearch'],
    });
}

function clearFilters() {
    filterForm.reset();
    router.get('/admin/dashboard', buildQuery(), {
        preserveState: true,
        only: ['activeQueue', 'activeQueueTotal', 'stats', 'filters', 'sort', 'archive', 'archiveSearch'],
    });
}

function sortBy(field: string) {
    const dir =
        props.sort.by === field && props.sort.dir === 'asc' ? 'desc' : 'asc';
    router.get('/admin/dashboard', buildQuery({ sort_by: field, sort_dir: dir }), {
        preserveState: true,
        only: ['activeQueue', 'activeQueueTotal', 'stats', 'filters', 'sort', 'archive', 'archiveSearch'],
    });
}

function searchArchive() {
    router.get('/admin/dashboard', buildQuery({ archive_search: archiveSearchForm.archive_search || undefined }), {
        preserveState: true,
        only: ['archive', 'archiveSearch'],
    });
}

function goToArchivePage(url: string | null) {
    if (!url) return;
    router.visit(url);
}

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
    <Head title="Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
            <!-- Stat cards -->
            <div class="grid gap-4 sm:grid-cols-3">
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
                            Active in queue
                        </p>
                        <p
                            class="mt-1 text-2xl font-semibold tabular-nums text-foreground"
                        >
                            {{ stats.activeInQueue }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Pending + Ongoing
                        </p>
                    </div>
                </div>
                <div
                    class="flex items-start gap-4 rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-white/10"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <Icon name="userCheck" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground"
                        >
                            Assigned to me
                        </p>
                        <p
                            class="mt-1 text-2xl font-semibold tabular-nums text-foreground"
                        >
                            {{ stats.assignedToMe }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Requests assigned to you
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
                            Total received
                        </p>
                        <p
                            class="mt-1 text-2xl font-semibold tabular-nums text-foreground"
                        >
                            {{ stats.totalReceived }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            All requests received
                        </p>
                    </div>
                </div>
            </div>

            <!-- Admin quick links (grouped at top) -->
            <div class="flex flex-col gap-3">
                <h2 class="text-sm font-semibold text-foreground">
                    Quick actions
                </h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <Link
                        href="/admin/qr-generator"
                        class="flex items-center gap-3 rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-white/10"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <Icon name="qrcode" class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-foreground">
                                QR Generator
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ totalGenerated }} codes generated
                            </p>
                        </div>
                    </Link>
                    <Link
                        href="/admin/enrollments/create"
                        class="flex items-center gap-3 rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-white/10"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <Icon name="clipboardList" class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-foreground">
                                Enroll ticket
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Add new enrollment
                            </p>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Requests Queue -->
            <div class="flex flex-col gap-3">
                <h2 class="text-sm font-semibold text-foreground">
                    Requests Queue
                </h2>
                <p class="text-xs text-muted-foreground">
                    Pending requests only (FIFO—oldest first). Requests with no status are not shown. Sort by date filed, control ticket, or requester; filter below.
                </p>

                <!-- Filters -->
                <div
                    class="rounded-xl border border-sidebar-border/60 bg-card p-3 shadow-sm dark:border-white/10"
                >
                    <form
                        class="flex flex-wrap items-end gap-3"
                        @submit.prevent="applyFilters"
                    >
                        <div class="min-w-[140px] flex-1">
                            <label
                                class="mb-1 block text-[11px] font-medium uppercase text-muted-foreground"
                            >
                                Control ticket
                            </label>
                            <input
                                v-model="filterForm.control_ticket_number"
                                type="text"
                                class="w-full rounded-md border border-sidebar-border/60 bg-background px-2.5 py-1.5 text-xs text-foreground dark:border-white/10"
                                placeholder="e.g. CTRL-001"
                            />
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <button
                                type="submit"
                                class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:opacity-90"
                            >
                                Apply
                            </button>
                            <button
                                v-if="hasActiveFilters"
                                type="button"
                                class="rounded-md border border-sidebar-border/60 px-3 py-1.5 text-xs font-medium text-foreground dark:border-white/10"
                                @click="clearFilters"
                            >
                                Clear
                            </button>
                        </div>
                    </form>
                </div>

                <div
                    class="overflow-hidden rounded-xl border border-sidebar-border/60 bg-card shadow-sm dark:border-white/10"
                >
                    <div class="max-h-[420px] overflow-auto">
                        <table class="w-full min-w-[800px] text-xs">
                            <thead
                                class="sticky top-0 z-10 border-b border-sidebar-border/60 bg-muted/50 text-[11px] uppercase tracking-wide text-muted-foreground dark:border-white/10 dark:bg-white/5"
                            >
                                <tr>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        <button
                                            type="button"
                                            class="flex items-center gap-1 hover:text-foreground"
                                            @click="sortBy('created_at')"
                                        >
                                            Date filed
                                            <Icon
                                                v-if="sort.by === 'created_at'"
                                                :name="sort.dir === 'asc' ? 'chevronUp' : 'chevronDown'"
                                                class="h-3.5 w-3.5"
                                            />
                                        </button>
                                    </th>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        <button
                                            type="button"
                                            class="flex items-center gap-1 hover:text-foreground"
                                            @click="sortBy('control_ticket_number')"
                                        >
                                            Control ticket
                                            <Icon
                                                v-if="sort.by === 'control_ticket_number'"
                                                :name="sort.dir === 'asc' ? 'chevronUp' : 'chevronDown'"
                                                class="h-3.5 w-3.5"
                                            />
                                        </button>
                                    </th>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        <button
                                            type="button"
                                            class="flex items-center gap-1 hover:text-foreground"
                                            @click="sortBy('requester_name')"
                                        >
                                            Requester
                                            <Icon
                                                v-if="sort.by === 'requester_name'"
                                                :name="sort.dir === 'asc' ? 'chevronUp' : 'chevronDown'"
                                                class="h-3.5 w-3.5"
                                            />
                                        </button>
                                    </th>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        Office
                                    </th>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        Category
                                    </th>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        Status
                                    </th>
                                    <th class="max-w-[160px] px-3 py-2.5 text-left font-semibold">
                                        Remarks
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-sidebar-border/60 text-foreground dark:divide-white/10"
                            >
                                <tr
                                    v-for="row in activeQueue"
                                    :key="row.id"
                                    class="hover:bg-muted/30 dark:hover:bg-white/5"
                                >
                                    <td class="whitespace-nowrap px-3 py-2 text-muted-foreground">
                                        {{ formatDate(row.dateFiled) }}
                                    </td>
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
                                    <td
                                        class="max-w-[140px] truncate px-3 py-2"
                                        :title="displayText(row.requesterName)"
                                    >
                                        {{ displayText(row.requesterName) }}
                                    </td>
                                    <td
                                        class="max-w-[120px] truncate px-3 py-2"
                                        :title="displayText(row.office)"
                                    >
                                        {{ displayText(row.office) }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <span
                                            :class="[
                                                badgeBase,
                                                resolveCategoryBadge(row.category),
                                            ]"
                                        >
                                            {{ displayText(row.category, '—') }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span
                                            :class="[
                                                badgeBase,
                                                resolveStatusBadge(row.status),
                                            ]"
                                        >
                                            {{ displayText(row.status, '—') }}
                                        </span>
                                    </td>
                                    <td
                                        class="max-w-[160px] truncate px-3 py-2 text-muted-foreground"
                                        :title="displayText(row.remarks)"
                                    >
                                        {{ displayText(row.remarks, '—') }}
                                    </td>
                                </tr>
                                <tr v-if="activeQueue.length === 0">
                                    <td
                                        colspan="8"
                                        class="px-4 py-8 text-center text-xs text-muted-foreground"
                                    >
                                        No pending requests in the queue.
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

            <!-- Archived Requests -->
            <div class="flex flex-col gap-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <button
                        type="button"
                        class="flex items-center gap-2 text-sm font-semibold text-foreground hover:underline"
                        @click="showArchive = !showArchive"
                    >
                        <Icon
                            :name="showArchive ? 'chevronDown' : 'chevronRight'"
                            class="h-4 w-4"
                        />
                        Archived Requests
                    </button>
                </div>

                <div
                    v-show="showArchive"
                    class="flex flex-col gap-3 rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-white/10"
                >
                    <form
                        class="flex flex-wrap items-end gap-3"
                        @submit.prevent="searchArchive"
                    >
                        <div class="min-w-[200px] flex-1">
                            <label
                                class="mb-1 block text-[11px] font-medium uppercase text-muted-foreground"
                            >
                                Search archive
                            </label>
                            <input
                                v-model="archiveSearchForm.archive_search"
                                type="text"
                                class="w-full rounded-md border border-sidebar-border/60 bg-background px-2.5 py-1.5 text-xs text-foreground dark:border-white/10"
                                placeholder="Control ticket or requester name"
                            />
                        </div>
                        <button
                            type="submit"
                            class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:opacity-90"
                        >
                            Search
                        </button>
                    </form>

                    <div class="flex flex-wrap items-end gap-3 rounded-lg border border-sidebar-border/40 bg-muted/30 p-3 dark:border-white/10 dark:bg-white/5">
                        <p class="w-full text-[11px] font-medium uppercase text-muted-foreground">
                            Download report 
                        </p>
                        <div class="min-w-[120px]">
                            <label
                                class="mb-1 block text-[11px] font-medium uppercase text-muted-foreground"
                            >
                                Date from
                            </label>
                            <input
                                v-model="exportDateFrom"
                                type="date"
                                class="w-full rounded-md border border-sidebar-border/60 bg-background px-2.5 py-1.5 text-xs text-foreground dark:border-white/10"
                            />
                        </div>
                        <div class="min-w-[120px]">
                            <label
                                class="mb-1 block text-[11px] font-medium uppercase text-muted-foreground"
                            >
                                Date to
                            </label>
                            <input
                                v-model="exportDateTo"
                                type="date"
                                class="w-full rounded-md border border-sidebar-border/60 bg-background px-2.5 py-1.5 text-xs text-foreground dark:border-white/10"
                            />
                        </div>
                        <a
                            :href="exportArchiveUrl"
                            class="inline-flex items-center gap-2 rounded-md border border-sidebar-border/60 bg-card px-3 py-1.5 text-xs font-medium text-foreground hover:bg-muted/50 dark:border-white/10"
                        >
                            <Icon name="download" class="h-4 w-4" />
                            Download Report
                        </a>
                        <p class="w-full text-[11px] text-muted-foreground">
                            Exports completed requests. Optional date range (max 365 days). Current search filter is applied.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-xs">
                            <thead
                                class="border-b border-sidebar-border/60 bg-muted/50 text-[11px] uppercase tracking-wide text-muted-foreground dark:border-white/10 dark:bg-white/5"
                            >
                                <tr>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        Control ticket
                                    </th>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        Requester
                                    </th>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        Office
                                    </th>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        Category
                                    </th>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        Status
                                    </th>
                                    <th class="px-3 py-2.5 text-left font-semibold">
                                        Date filed
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-sidebar-border/60 text-foreground dark:divide-white/10"
                            >
                                <tr
                                    v-for="item in archive.data"
                                    :key="item.id"
                                    class="hover:bg-muted/30 dark:hover:bg-white/5"
                                >
                                    <td class="px-3 py-2 font-medium">
                                        <Link
                                            v-if="item.showUrl"
                                            :href="item.showUrl"
                                            class="text-primary hover:underline"
                                        >
                                            {{ item.controlTicketNumber }}
                                        </Link>
                                        <span v-else>{{ item.controlTicketNumber }}</span>
                                    </td>
                                    <td
                                        class="max-w-[140px] truncate px-3 py-2"
                                        :title="displayText(item.requesterName)"
                                    >
                                        {{ displayText(item.requesterName) }}
                                    </td>
                                    <td
                                        class="max-w-[120px] truncate px-3 py-2"
                                    >
                                        {{ displayText(item.office) }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <span
                                            :class="[
                                                badgeBase,
                                                resolveCategoryBadge(item.category),
                                            ]"
                                        >
                                            {{ displayText(item.category, '—') }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span
                                            :class="[
                                                badgeBase,
                                                resolveStatusBadge(item.status),
                                            ]"
                                        >
                                            {{ displayText(item.status, '—') }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2 text-muted-foreground">
                                        {{ formatDate(item.dateFiled) }}
                                    </td>
                                </tr>
                                <tr v-if="archive.data.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-4 py-8 text-center text-xs text-muted-foreground"
                                    >
                                        No archived requests.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="archive.last_page > 1"
                        class="flex flex-wrap items-center justify-center gap-2 pt-2"
                    >
                        <template
                            v-for="(link, index) in archive.links"
                            :key="index"
                        >
                            <button
                                v-if="link.url"
                                type="button"
                                class="rounded border border-sidebar-border/60 px-2 py-1 text-xs text-foreground hover:bg-muted/50 dark:border-white/10"
                                :class="{
                                    'bg-primary text-primary-foreground': link.active,
                                }"
                                v-html="link.label"
                                @click="goToArchivePage(link.url)"
                            />
                            <span
                                v-else
                                class="px-2 py-1 text-xs text-muted-foreground"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
