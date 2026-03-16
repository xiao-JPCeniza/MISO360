<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import Icon from '@/components/Icon.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

type Month = {
    key: number;
    label: string;
    short: string;
};

type ReportRow = {
    id: number;
    nature: string;
    counts: Record<string, number>;
    total: number;
};

const props = defineProps<{
    year: number;
    months: Month[];
    rows: ReportRow[];
    monthTotals: Record<number, number>;
    grandTotal: number;
    preparedBy: { name: string | null; positionTitle: string | null };
}>();

const page = usePage();
const isSuperAdmin = computed(
    () => page.props.auth?.user?.role === 'super_admin',
);

const breadcrumbs: BreadcrumbItem[] = [
    ...(isSuperAdmin.value
        ? [{ title: 'Admin Dashboard', href: '/admin/dashboard' } as BreadcrumbItem]
        : []),
    {
        title: 'Nature of Requests Report',
        href: '/admin/reports/nature-of-requests',
    },
];

const yearInput = ref<number>(props.year);

const generatedAt = computed(() =>
    new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'long',
        day: '2-digit',
    }).format(new Date()),
);

function applyYear() {
    router.get(
        '/admin/reports/nature-of-requests',
        { year: yearInput.value },
        { preserveState: true },
    );
}

function printReport() {
    window.print();
}
</script>

<template>
    <Head title="Nature of Requests Report" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-4">
            <div class="report-actions flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <Link
                        v-if="isSuperAdmin"
                        href="/admin/dashboard"
                        class="inline-flex items-center gap-2 rounded-md border border-sidebar-border/60 bg-card px-3 py-1.5 text-xs font-medium text-foreground hover:bg-muted/50 dark:border-white/10"
                    >
                        <Icon name="arrowLeft" class="h-4 w-4" />
                        Back
                    </Link>
                    <div class="text-xs text-muted-foreground">
                        Generated {{ generatedAt }}
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                        Year
                    </label>
                    <input
                        v-model.number="yearInput"
                        type="number"
                        min="2000"
                        max="2100"
                        class="h-9 w-28 rounded-md border border-sidebar-border/60 bg-background px-2 text-sm text-foreground dark:border-white/10"
                    />
                    <button
                        type="button"
                        class="h-9 rounded-md border border-sidebar-border/60 bg-card px-3 text-xs font-medium text-foreground hover:bg-muted/50 dark:border-white/10"
                        @click="applyYear"
                    >
                        Apply
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center gap-2 rounded-md bg-primary px-3 text-xs font-medium text-primary-foreground hover:opacity-90"
                        @click="printReport"
                    >
                        <Icon name="printer" class="h-4 w-4" />
                        Print Report
                    </button>
                </div>
            </div>

            <div
                class="rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-white/10 print:border-0 print:bg-white print:p-0 print:shadow-none"
            >
                <div class="mb-3 text-center print:mb-2">
                    <div class="text-sm font-semibold text-foreground print:text-black">
                        Nature of Requests Report
                    </div>
                    <div class="text-xs text-muted-foreground print:text-black">
                        Year {{ year }}
                    </div>
                </div>

                <div class="overflow-x-auto print:overflow-visible">
                    <table class="w-full min-w-[980px] border-collapse text-[11px] print:min-w-0">
                        <thead>
                            <tr>
                                <th
                                    class="border border-slate-900/80 bg-muted/30 px-2 py-1 text-center font-semibold text-foreground dark:border-white/15 dark:bg-white/5 print:border-black print:bg-white print:text-black"
                                    colspan="2"
                                >
                                    MISO
                                </th>
                                <th
                                    class="border border-slate-900/80 bg-muted/30 px-2 py-1 text-center font-semibold text-foreground dark:border-white/15 dark:bg-white/5 print:border-black print:bg-white print:text-black"
                                    :colspan="months.length + 1"
                                >
                                    Number of Transactions or Instances
                                </th>
                            </tr>
                            <tr>
                                <th
                                    class="border border-slate-900/80 px-2 py-1 text-left font-semibold text-foreground dark:border-white/15 print:border-black print:text-black"
                                >
                                    Type
                                </th>
                                <th
                                    class="border border-slate-900/80 px-2 py-1 text-left font-semibold text-foreground dark:border-white/15 print:border-black print:text-black"
                                >
                                    Nature of Request
                                </th>
                                <th
                                    v-for="month in months"
                                    :key="month.key"
                                    class="border border-slate-900/80 px-2 py-1 text-center font-semibold text-foreground dark:border-white/15 print:border-black print:text-black"
                                >
                                    {{ month.short }}
                                </th>
                                <th
                                    class="border border-slate-900/80 px-2 py-1 text-center font-semibold text-foreground dark:border-white/15 print:border-black print:text-black"
                                >
                                    Total
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="row in rows" :key="row.id">
                                <td
                                    class="border border-slate-900/80 px-2 py-1 text-left text-foreground dark:border-white/15 print:border-black print:text-black"
                                >
                                    Internal
                                </td>
                                <td
                                    class="border border-slate-900/80 px-2 py-1 text-left text-foreground dark:border-white/15 print:border-black print:text-black"
                                >
                                    {{ row.nature }}
                                </td>
                                <td
                                    v-for="month in months"
                                    :key="month.key"
                                    class="border border-slate-900/80 px-2 py-1 text-center tabular-nums text-foreground dark:border-white/15 print:border-black print:text-black"
                                >
                                    {{ row.counts[String(month.key)] ?? 0 }}
                                </td>
                                <td
                                    class="border border-slate-900/80 px-2 py-1 text-center font-semibold tabular-nums text-foreground dark:border-white/15 print:border-black print:text-black"
                                >
                                    {{ row.total }}
                                </td>
                            </tr>

                            <tr v-if="rows.length === 0">
                                <td
                                    class="border border-slate-900/80 px-2 py-4 text-center text-muted-foreground dark:border-white/15 print:border-black print:text-black"
                                    :colspan="months.length + 3"
                                >
                                    No data available for {{ year }}.
                                </td>
                            </tr>
                        </tbody>

                        <tfoot>
                            <tr>
                                <td
                                    class="border border-slate-900/80 px-2 py-1 font-semibold text-foreground dark:border-white/15 print:border-black print:text-black"
                                    colspan="2"
                                >
                                    Total
                                </td>
                                <td
                                    v-for="month in months"
                                    :key="month.key"
                                    class="border border-slate-900/80 px-2 py-1 text-center font-semibold tabular-nums text-foreground dark:border-white/15 print:border-black print:text-black"
                                >
                                    {{ monthTotals[month.key] ?? 0 }}
                                </td>
                                <td
                                    class="border border-slate-900/80 px-2 py-1 text-center font-semibold tabular-nums text-foreground dark:border-white/15 print:border-black print:text-black"
                                >
                                    {{ grandTotal }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-10 grid gap-8 text-xs text-foreground print:mt-8 print:text-black md:grid-cols-2">
                    <div class="space-y-4">
                        <div class="text-xs font-semibold">Prepared by:</div>
                        <div class="space-y-1">
                            <div class="h-6 border-b border-slate-900/80 print:border-black">
                                <span class="text-sm font-semibold">
                                    {{ preparedBy.name ?? '' }}
                                </span>
                            </div>
                            <div class="text-[11px] text-muted-foreground print:text-black">
                                {{ preparedBy.positionTitle ?? '' }}
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="text-xs font-semibold">Reviewed and Approved by:</div>
                        <div class="space-y-1">
                            <div class="h-6 border-b border-slate-900/80 print:border-black"></div>
                            <div class="text-[11px] text-muted-foreground print:text-black">
                                (Signature / Name)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    .report-actions {
        display: none !important;
    }

    @page {
        size: A4 landscape;
        margin: 12mm;
    }
}
</style>

