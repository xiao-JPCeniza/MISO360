<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/admin/dashboard',
    },
];
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <div class="grid gap-4 xl:grid-cols-4">
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        Active Users
                    </p>
                    <p class="mt-4 text-3xl font-semibold">128</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Logged in within the last 30 days.
                    </p>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        Open Tickets
                    </p>
                    <p class="mt-4 text-3xl font-semibold">42</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Requests needing assignment or review.
                    </p>
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
