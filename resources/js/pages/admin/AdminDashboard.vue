<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/admin/dashboard',
    },
];

const props = defineProps<{
    totalGenerated: number;
}>();

const totalGenerated = computed(() => props.totalGenerated ?? 0);

</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <div class="grid gap-4 xl:grid-cols-4">
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        QR Code Generator
                    </p>
                    <p class="mt-4 text-3xl font-semibold">{{ totalGenerated }}</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Total QR codes generated across all batches.
                    </p>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <Link
                            href="/admin/qr-generator"
                            class="rounded-full bg-[#2563eb] px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white transition-colors hover:bg-[#1d4ed8]"
                        >
                            Open QR Generator
                        </Link>
                    </div>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        Open Tickets
                    </p>
                    <p class="mt-4 text-3xl font-semibold">42</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Requests needing assignment or review.
                    </p>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <Link
                            href="/admin/enrollments/create"
                            class="rounded-full border border-[#0f172a]/20 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[#0f172a] transition-colors hover:bg-[#0f172a]/5"
                        >
                            Enroll Ticket
                        </Link>
                    </div>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        SLA Compliance
                    </p>
                    <p class="mt-4 text-3xl font-semibold">96%</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        On-time delivery for the quarter.
                    </p>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        Pending Approvals
                    </p>
                    <p class="mt-4 text-3xl font-semibold">7</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Items waiting on admin sign-off.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-6">
                    <h2 class="text-lg font-semibold">Operational insights</h2>
                    <div class="mt-4 space-y-4 text-sm text-muted-foreground">
                        <div class="rounded-xl bg-muted/40 px-4 py-3">
                            14 workflows are due for quarterly review this month.
                        </div>
                        <div class="rounded-xl bg-muted/40 px-4 py-3">
                            Resource allocation is trending 12% above forecast.
                        </div>
                        <div class="rounded-xl bg-muted/40 px-4 py-3">
                            Service desk backlog improved by 8% week over week.
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-6">
                    <h2 class="text-lg font-semibold">Admin actions</h2>
                    <ul class="mt-4 space-y-3 text-sm text-muted-foreground">
                        <li class="rounded-xl bg-muted/40 px-4 py-3">
                            Review access requests for new units
                        </li>
                        <li class="rounded-xl bg-muted/40 px-4 py-3">
                            Approve draft policy update
                        </li>
                        <li class="rounded-xl bg-muted/40 px-4 py-3">
                            Publish quarterly dashboard summary
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
