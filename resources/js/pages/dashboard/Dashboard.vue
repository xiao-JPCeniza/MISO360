<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="User Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <div class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        Open Requests
                    </p>
                    <p class="mt-4 text-3xl font-semibold">8</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Items awaiting review or additional details.
                    </p>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        In Progress
                    </p>
                    <p class="mt-4 text-3xl font-semibold">5</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Active workstreams tracked this week.
                    </p>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        Completed
                    </p>
                    <p class="mt-4 text-3xl font-semibold">24</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Resolved items from the past 30 days.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-6">
                    <h2 class="text-lg font-semibold">Upcoming milestones</h2>
                    <div class="mt-4 space-y-3 text-sm text-muted-foreground">
                        <div class="flex items-center justify-between rounded-xl bg-muted/40 px-4 py-3">
                            <span>Quarterly service review</span>
                            <span class="font-medium text-foreground">Mar 18</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-muted/40 px-4 py-3">
                            <span>Policy refresh kickoff</span>
                            <span class="font-medium text-foreground">Mar 26</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-muted/40 px-4 py-3">
                            <span>Resource planning sync</span>
                            <span class="font-medium text-foreground">Apr 02</span>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-6">
                    <h2 class="text-lg font-semibold">Your queue</h2>
                    <ul class="mt-4 space-y-3 text-sm text-muted-foreground">
                        <li class="rounded-xl bg-muted/40 px-4 py-3">
                            Update request summary for MSO-145
                        </li>
                        <li class="rounded-xl bg-muted/40 px-4 py-3">
                            Upload revised staffing worksheet
                        </li>
                        <li class="rounded-xl bg-muted/40 px-4 py-3">
                            Close out vendor review notes
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
