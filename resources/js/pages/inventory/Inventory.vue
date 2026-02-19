<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type InventoryItem = {
    id: number;
    rowKey: string;
    uniqueId: string;
    equipmentName: string;
    equipmentType: string | null;
    brand: string | null;
    model: string | null;
    serialNumber: string | null;
    assetTag: string | null;
    status: 'active' | 'archived';
    archivedAt?: string | null;
};

type BorrowedRequest = {
    id: number;
    controlTicketNumber: string;
    requesterName: string | null;
    office: string | null;
    status: string | null;
    dateFiled: string | null;
    showUrl: string;
};

const props = defineProps<{
    items: InventoryItem[];
    borrowedRequests: BorrowedRequest[];
    filters: {
        search: string;
        status: string;
    };
    counts: {
        active: number;
        archived: number;
        borrowed: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Inventory',
        href: '/inventory',
    },
];

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'all');
const isFiltered = computed(
    () => search.value.trim().length > 0 || status.value !== 'all',
);

let searchTimer: ReturnType<typeof setTimeout> | null = null;

function applyFilters() {
    router.get(
        '/inventory',
        {
            search: search.value.trim(),
            status: status.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}

function scheduleSearch() {
    if (searchTimer) {
        window.clearTimeout(searchTimer);
    }
    searchTimer = window.setTimeout(() => {
        applyFilters();
    }, 300);
}

function setStatus(nextStatus: string) {
    status.value = nextStatus;
    applyFilters();
}

const borrowedPollIntervalMs = 45_000;
let borrowedPollTimer: ReturnType<typeof setInterval> | null = null;

function startBorrowedPolling() {
    if (borrowedPollTimer) return;
    borrowedPollTimer = window.setInterval(() => {
        applyFilters();
    }, borrowedPollIntervalMs);
}

function stopBorrowedPolling() {
    if (borrowedPollTimer) {
        window.clearInterval(borrowedPollTimer);
        borrowedPollTimer = null;
    }
}

onMounted(() => {
    if (status.value === 'borrowed') startBorrowedPolling();
});

onUnmounted(() => {
    stopBorrowedPolling();
});

watch(status, (next) => {
    if (next === 'borrowed') startBorrowedPolling();
    else stopBorrowedPolling();
});
</script>

<template>
    <Head title="Inventory" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <section class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <p class="text-xs uppercase tracking-[0.3em] text-muted-foreground">
                            Inventory
                        </p>
                        <h1 class="text-2xl font-semibold">Inventory registry</h1>
                        <p class="text-sm text-muted-foreground">
                            Track registered equipment and archived assets in one consolidated view.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link
                            href="/admin/enrollments/create"
                            class="rounded-full bg-[#0f172a] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#1e293b]"
                        >
                            Enroll new item
                        </Link>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        Active assets
                    </p>
                    <p class="mt-4 text-3xl font-semibold">{{ counts.active }}</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Currently operational in inventory.
                    </p>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        Archived
                    </p>
                    <p class="mt-4 text-3xl font-semibold">{{ counts.archived }}</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Deactivated or retired equipment.
                    </p>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        Borrowed
                    </p>
                    <p class="mt-4 text-3xl font-semibold">{{ counts.borrowed }}</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Pending borrow requests. Completed requests are removed.
                    </p>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        Total registry
                    </p>
                    <p class="mt-4 text-3xl font-semibold">
                        {{ counts.active + counts.archived }}
                    </p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        All items registered in the system.
                    </p>
                </div>
            </section>

            <section class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative">
                            <input
                                v-model="search"
                                type="text"
                                class="w-full rounded-full border border-sidebar-border/70 bg-background px-4 py-2.5 text-sm"
                                placeholder="Search by equipment name"
                                @input="scheduleSearch"
                            />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] transition"
                                :class="status === 'all'
                                    ? 'border-transparent bg-[#0f172a] text-white'
                                    : 'border-sidebar-border/70 text-muted-foreground hover:bg-muted/40'"
                                @click="setStatus('all')"
                            >
                                All
                            </button>
                            <button
                                type="button"
                                class="rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] transition"
                                :class="status === 'active'
                                    ? 'border-transparent bg-emerald-600 text-white'
                                    : 'border-sidebar-border/70 text-muted-foreground hover:bg-muted/40'"
                                @click="setStatus('active')"
                            >
                                Active
                            </button>
                            <button
                                type="button"
                                class="rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] transition"
                                :class="status === 'archived'
                                    ? 'border-transparent bg-amber-600 text-white'
                                    : 'border-sidebar-border/70 text-muted-foreground hover:bg-muted/40'"
                                @click="setStatus('archived')"
                            >
                                Archived
                            </button>
                            <button
                                type="button"
                                class="rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] transition"
                                :class="status === 'borrowed'
                                    ? 'border-transparent bg-blue-600 text-white'
                                    : 'border-sidebar-border/70 text-muted-foreground hover:bg-muted/40'"
                                @click="setStatus('borrowed')"
                            >
                                Borrowed
                            </button>
                        </div>
                    </div>
                    <p v-if="isFiltered && status !== 'borrowed'" class="text-xs text-muted-foreground">
                        Showing {{ items.length }} result(s) for current filters.
                    </p>
                    <p v-else-if="status === 'borrowed'" class="text-xs text-muted-foreground">
                        {{ borrowedRequests.length }} active borrow request(s). Completed requests are removed from this list.
                    </p>
                </div>

                <!-- Borrowed requests list (when Borrowed tab is selected) -->
                <div v-if="status === 'borrowed'" class="mt-6 overflow-hidden rounded-2xl border border-sidebar-border/60">
                    <div v-if="!borrowedRequests.length" class="rounded-2xl border border-dashed border-sidebar-border/70 p-8 text-center text-sm text-muted-foreground">
                        No active borrow requests. Completed borrows are removed from this list.
                    </div>
                    <template v-else>
                        <div class="hidden grid-cols-[1fr_1fr_0.8fr_0.6fr_0.6fr] gap-4 border-b border-sidebar-border/60 bg-muted/30 px-4 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground md:grid">
                            <span>Control ticket</span>
                            <span>Requester</span>
                            <span>Office</span>
                            <span>Status</span>
                            <span class="text-right">Actions</span>
                        </div>
                        <div class="divide-y divide-sidebar-border/60">
                            <div
                                v-for="req in borrowedRequests"
                                :key="req.id"
                                class="flex flex-col gap-3 px-4 py-4 text-sm md:grid md:grid-cols-[1fr_1fr_0.8fr_0.6fr_0.6fr] md:items-center md:gap-4"
                            >
                                <p class="font-semibold">{{ req.controlTicketNumber }}</p>
                                <p class="text-muted-foreground">{{ req.requesterName ?? '—' }}</p>
                                <p class="text-muted-foreground">{{ req.office ?? '—' }}</p>
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-200">
                                    {{ req.status ?? '—' }}
                                </span>
                                <div class="flex justify-end">
                                    <Link
                                        :href="req.showUrl"
                                        class="rounded-full border border-sidebar-border/70 px-3 py-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:bg-muted/40"
                                    >
                                        View
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div v-else-if="!items.length" class="mt-6 rounded-2xl border border-dashed border-sidebar-border/70 p-8 text-center text-sm text-muted-foreground">
                    No inventory records match your filters.
                </div>

                <div v-else class="mt-6 overflow-hidden rounded-2xl border border-sidebar-border/60">
                    <div class="hidden grid-cols-[1.2fr_0.8fr_0.6fr_0.6fr] gap-4 border-b border-sidebar-border/60 bg-muted/30 px-4 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground md:grid">
                        <span>Equipment</span>
                        <span>Unique ID</span>
                        <span>Status</span>
                        <span class="text-right">Actions</span>
                    </div>
                    <div class="divide-y divide-sidebar-border/60">
                        <div
                            v-for="item in items"
                            :key="item.rowKey"
                            class="flex flex-col gap-3 px-4 py-4 text-sm md:grid md:grid-cols-[1.2fr_0.8fr_0.6fr_0.6fr] md:items-center md:gap-4"
                        >
                            <div>
                                <p class="font-semibold">{{ item.equipmentName }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ item.brand || 'Unspecified brand' }}
                                    <span v-if="item.model"> • {{ item.model }}</span>
                                </p>
                            </div>
                            <p class="text-xs font-semibold tracking-[0.2em] text-muted-foreground">
                                {{ item.uniqueId }}
                            </p>
                            <span
                                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold"
                                :class="item.status === 'active'
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200'
                                    : 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200'"
                            >
                                {{ item.status === 'active' ? 'Active' : 'Archived' }}
                            </span>
                            <div class="flex justify-end">
                                <Link
                                    :href="`/inventory/${item.uniqueId}`"
                                    class="rounded-full border border-sidebar-border/70 px-3 py-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:bg-muted/40"
                                >
                                    View
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
