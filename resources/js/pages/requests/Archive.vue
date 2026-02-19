<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import Icon from '@/components/Icon.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    getCategoryBadgeClass,
    getStatusBadgeClass,
    requestBadgeBase,
} from '@/lib/requestBadges';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type TicketRequestRow = {
    id: number;
    controlTicketNumber: string;
    requestedBy: string | null;
    positionTitle: string | null;
    office: string | null;
    dateFiled: string | null;
    natureOfRequest: string | null;
    requestDescription: string | null;
    remarks: string | null;
    assignedStaff: string | null;
    status: string | null;
    category: string | null;
    estimatedCompletionDate: string | null;
    showUrl: string | null;
};

const props = defineProps<{
    requests: TicketRequestRow[];
    isAdmin: boolean;
}>();

const pageTitle = computed(() => (props.isAdmin ? 'Archived Requests' : 'My Archived Requests'));

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: props.isAdmin ? 'Requests' : 'My Requests',
        href: '/requests',
    },
    {
        title: 'Archive',
        href: '/requests/archive',
    },
]);

const searchQuery = ref('');

const badgeBase =
    'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium leading-4';

const normalizeValue = (value: string | null | undefined): string =>
    (value ?? '').toLowerCase();

const resolveNatureBadgeTone = (value: string | null) => {
    const n = normalizeValue(value);
    const emerald = 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    const blue = 'border-blue-500/30 bg-blue-500/10 text-blue-700 dark:text-blue-300';
    const amber = 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300';
    const purple = 'border-purple-500/30 bg-purple-500/10 text-purple-700 dark:text-purple-300';
    const slate = 'border-slate-500/20 bg-slate-500/10 text-slate-600 dark:text-slate-300';
    if (n.includes('password')) return blue;
    if (n.includes('repair')) return amber;
    if (n.includes('system')) return purple;
    return slate;
};

const resolveRemarksBadgeTone = (value: string | null) => {
    const n = normalizeValue(value);
    const blue = 'border-blue-500/30 bg-blue-500/10 text-blue-700 dark:text-blue-300';
    const purple = 'border-purple-500/30 bg-purple-500/10 text-purple-700 dark:text-purple-300';
    const emerald = 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    const amber = 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300';
    const slate = 'border-slate-500/20 bg-slate-500/10 text-slate-600 dark:text-slate-300';
    if (n.includes('deliver')) return blue;
    if (n.includes('interview')) return purple;
    if (n.includes('remote')) return emerald;
    if (n.includes('pick')) return amber;
    return slate;
};

const formatDate = (value: string | null): string => {
    if (!value) return '—';
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return value;
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    }).format(parsed);
};

const displayText = (value: string | null, fallback = '—'): string =>
    value && value.trim().length > 0 ? value : fallback;

const displayBadge = (value: string | null, fallback = 'Not set'): string =>
    value && value.trim().length > 0 ? value : fallback;

const itGovernanceTypes = [
    'system account creation',
    'system modification',
    'password reset or account recovery (gov mail)',
    'system error / bug report',
    'request for new system module or enhancement',
    'system development',
];

const equipmentAndNetworkTypes = [
    'software license or activation request',
    'computer repair',
    'laptop repair',
    'printer repair',
    'cctv issue/repair',
    'end-user equipment installation, setup, and configuration (connection of computers, monitors, printers, peripherals, and workstation relocation)',
    'request for new it equipment (e.g., pc, printer, ups)',
    'install/reformat operating system',
    'installation of application software',
    'network connectivity installation, repair, and maintenance services (lan and fiber optic cabling, network and wireless setup, repairs, upgrades, and network equipment deployment)',
    'end-user devices component replacement',
    'assess extent of hardware/software failure',
    'system reinstallation/troubleshooting (toims, gaams, ecpac)',
    'inspect unit',
    'borrow unit',
    'data recovery',
];

const isItGovernanceRequest = (value: string | null): boolean => {
    const normalized = normalizeValue(value);
    return itGovernanceTypes.some((type) => normalized === type);
};

const isEquipmentAndNetworkRequest = (value: string | null): boolean => {
    const normalized = normalizeValue(value);
    return equipmentAndNetworkTypes.some((type) => normalized === type);
};

const resolveActionUrl = (request: TicketRequestRow): string | null => {
    if (isItGovernanceRequest(request.natureOfRequest)) return `/requests/${request.id}/it-governance`;
    if (isEquipmentAndNetworkRequest(request.natureOfRequest)) return `/requests/${request.id}/equipment-and-network`;
    return request.showUrl;
};

const filteredRequests = computed(() => {
    const normalized = normalizeValue(searchQuery.value.trim());
    if (!normalized) return props.requests;
    if (props.isAdmin) {
        return props.requests.filter((request) => {
            const haystack = [request.controlTicketNumber, request.requestedBy]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();
            return haystack.includes(normalized);
        });
    }
    return props.requests.filter((request) => {
        const haystack = [
            request.controlTicketNumber,
            request.natureOfRequest,
            request.office,
            request.requestedBy,
            request.assignedStaff,
            request.category,
            request.remarks,
            request.status,
            request.estimatedCompletionDate,
            formatDate(request.dateFiled),
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();
        return haystack.includes(normalized);
    });
});
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div v-if="props.isAdmin" class="flex flex-1 flex-col bg-background p-5 sm:p-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <Link
                    href="/requests"
                    class="inline-flex w-fit items-center gap-1.5 text-xs text-muted-foreground transition hover:text-foreground"
                >
                    <Icon name="arrowLeft" class="h-3.5 w-3.5" />
                    Back to active requests
                </Link>
                <label class="relative w-full max-w-xs">
                    <span class="sr-only">Search Request Here</span>
                    <input
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search Request Here"
                        class="h-9 w-full rounded-md border border-input bg-background px-3 pr-9 text-xs text-foreground shadow-sm transition placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/50"
                    />
                    <Icon
                        name="search"
                        class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                    />
                </label>
            </div>
            <p class="mt-1 text-[11px] text-muted-foreground">
                Showing {{ filteredRequests.length }} of {{ props.requests.length }} archived requests
            </p>

            <div class="mt-3 rounded-md border border-border bg-card shadow-sm dark:border-white/10">
                <div class="max-h-[560px] overflow-auto">
                    <table class="w-full min-w-[980px] text-[11px] text-foreground">
                        <thead class="border-b border-border bg-muted/50 text-[11px] uppercase text-foreground dark:border-white/10 dark:bg-white/5">
                            <tr>
                                <th colspan="10" class="px-3 py-2 text-center text-xs font-semibold">
                                    ARCHIVED REQUEST FORM
                                </th>
                            </tr>
                            <tr class="border-t border-border dark:border-white/10">
                                <th class="px-3 py-2 text-left font-semibold">Control Ticket No.</th>
                                <th class="px-3 py-2 text-left font-semibold">Requested By</th>
                                <th class="px-3 py-2 text-left font-semibold">Position/Designation</th>
                                <th class="px-3 py-2 text-left font-semibold">Office</th>
                                <th class="px-3 py-2 text-left font-semibold">Date Filed</th>
                                <th class="px-3 py-2 text-left font-semibold">Nature of Request</th>
                                <th class="px-3 py-2 text-left font-semibold">Request Description</th>
                                <th class="px-3 py-2 text-left font-semibold">Status</th>
                                <th class="px-3 py-2 text-left font-semibold">Category</th>
                                <th class="px-3 py-2 text-left font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border text-xs text-foreground dark:divide-white/10">
                            <tr
                                v-for="request in filteredRequests"
                                :key="request.id"
                                class="odd:bg-background even:bg-muted/20 hover:bg-muted/40 dark:even:bg-white/5 dark:hover:bg-white/10"
                            >
                                <td class="px-3 py-2 font-medium">
                                    {{ request.controlTicketNumber }}
                                </td>
                                <td class="px-3 py-2">
                                    <span class="block max-w-[140px] truncate" :title="displayText(request.requestedBy)">
                                        {{ displayText(request.requestedBy) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="block max-w-[140px] truncate" :title="displayText(request.positionTitle)">
                                        {{ displayText(request.positionTitle) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="block max-w-[140px] truncate" :title="displayText(request.office)">
                                        {{ displayText(request.office) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    {{ formatDate(request.dateFiled) }}
                                </td>
                                <td class="px-3 py-2">
                                    <span class="block max-w-[160px] truncate" :title="displayText(request.natureOfRequest)">
                                        {{ displayText(request.natureOfRequest, 'Unspecified') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <span
                                        class="block max-w-[220px] truncate"
                                        :title="displayText(request.requestDescription)"
                                    >
                                        {{ displayText(request.requestDescription) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <span
                                        :class="[requestBadgeBase, getStatusBadgeClass(request.status)]"
                                        :title="displayBadge(request.status)"
                                    >
                                        {{ displayBadge(request.status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <span
                                        :class="[requestBadgeBase, getCategoryBadgeClass(request.category)]"
                                        :title="displayBadge(request.category)"
                                    >
                                        {{ displayBadge(request.category) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <Link
                                        v-if="resolveActionUrl(request)"
                                        :href="resolveActionUrl(request) || ''"
                                        class="inline-flex h-6 w-6 items-center justify-center rounded border border-border text-primary transition hover:bg-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring dark:border-white/20 dark:hover:bg-white/10"
                                        title="View request"
                                    >
                                        <Icon name="moreHorizontal" class="h-3.5 w-3.5" />
                                    </Link>
                                    <span
                                        v-else
                                        class="inline-flex h-6 w-6 items-center justify-center rounded border border-border text-muted-foreground dark:border-white/20"
                                    >
                                        <Icon name="moreHorizontal" class="h-3.5 w-3.5" />
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="filteredRequests.length === 0">
                                <td colspan="10" class="px-4 py-10 text-center text-xs text-muted-foreground">
                                    No archived requests match your search.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-else class="flex flex-1 flex-col gap-5 p-6">
            <div class="flex flex-col gap-3">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                            {{ pageTitle }}
                        </p>
                        <h1 class="text-xl font-semibold text-foreground">
                            {{ pageTitle }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Completed requests are archived here for review.
                        </p>
                    </div>
                    <Link
                        href="/requests"
                        class="inline-flex w-fit items-center gap-1.5 text-sm text-muted-foreground transition hover:text-foreground"
                    >
                        <Icon name="arrowLeft" class="h-4 w-4" />
                        Back to active requests
                    </Link>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <label class="relative w-full sm:max-w-sm">
                        <span class="sr-only">Search Request Here</span>
                        <input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Search Request Here"
                            class="h-9 w-full rounded-full border border-sidebar-border/70 bg-background px-4 pr-10 text-sm text-foreground shadow-sm transition focus:border-primary/50 focus:outline-none focus:ring-2 focus:ring-primary/15"
                        />
                        <Icon
                            name="search"
                            class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                        />
                    </label>
                    <p class="text-xs text-muted-foreground">
                        Showing {{ filteredRequests.length }} of {{ props.requests.length }} archived requests
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-sidebar-border/60 bg-background shadow-sm">
                <div class="max-h-[560px] overflow-auto">
                    <table class="w-full min-w-[1200px] text-sm">
                        <thead
                            class="sticky top-0 z-10 border-b border-sidebar-border/70 bg-background/95 text-xs uppercase tracking-wide text-muted-foreground backdrop-blur"
                        >
                            <tr>
                                <th class="px-3 py-3 text-left font-semibold">Control Ticket No.</th>
                                <th class="px-3 py-3 text-left font-semibold">Requested By</th>
                                <th class="px-3 py-3 text-left font-semibold">Office</th>
                                <th class="px-3 py-3 text-left font-semibold">Date Filed</th>
                                <th class="px-3 py-3 text-left font-semibold">Nature of Request</th>
                                <th class="px-3 py-3 text-left font-semibold">Remarks</th>
                                <th class="px-3 py-3 text-left font-semibold">Assigned IT Staff</th>
                                <th class="px-3 py-3 text-left font-semibold">Status</th>
                                <th class="px-3 py-3 text-left font-semibold">Category</th>
                                <th class="px-3 py-3 text-left font-semibold">Est. Completion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-muted/40 text-sm text-foreground">
                            <tr
                                v-for="request in filteredRequests"
                                :key="request.id"
                                class="odd:bg-muted/15 even:bg-background hover:bg-muted/30"
                            >
                                <td class="px-3 py-2 font-medium">
                                    <Link
                                        v-if="request.showUrl"
                                        :href="request.showUrl"
                                        class="text-primary transition hover:text-primary/80"
                                    >
                                        {{ request.controlTicketNumber }}
                                    </Link>
                                    <span v-else>{{ request.controlTicketNumber }}</span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="block max-w-[140px] truncate" :title="displayText(request.requestedBy)">
                                        {{ displayText(request.requestedBy) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="block max-w-[140px] truncate" :title="displayText(request.office)">
                                        {{ displayText(request.office) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    {{ formatDate(request.dateFiled) }}
                                </td>
                                <td class="px-3 py-2">
                                    <span
                                        :class="[badgeBase, resolveNatureBadgeTone(request.natureOfRequest)]"
                                        :title="displayBadge(request.natureOfRequest, 'Unspecified')"
                                    >
                                        {{ displayBadge(request.natureOfRequest, 'Unspecified') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <span
                                        :class="[badgeBase, resolveRemarksBadgeTone(request.remarks)]"
                                        :title="displayBadge(request.remarks)"
                                    >
                                        {{ displayBadge(request.remarks) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="block max-w-[150px] truncate" :title="displayText(request.assignedStaff)">
                                        {{ displayText(request.assignedStaff, 'Not assigned') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <span
                                        :class="[requestBadgeBase, getStatusBadgeClass(request.status)]"
                                        :title="displayBadge(request.status)"
                                    >
                                        {{ displayBadge(request.status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <span
                                        :class="[requestBadgeBase, getCategoryBadgeClass(request.category)]"
                                        :title="displayBadge(request.category)"
                                    >
                                        {{ displayBadge(request.category) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    {{ formatDate(request.estimatedCompletionDate) }}
                                </td>
                            </tr>
                            <tr v-if="filteredRequests.length === 0">
                                <td colspan="10" class="px-4 py-10 text-center text-sm text-muted-foreground">
                                    No archived requests match your search.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
