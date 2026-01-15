<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'List of Requests',
        href: '/requests',
    },
];
</script>

<template>
    <Head title="List of Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <div class="space-y-2">
                <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                    Requests
                </p>
                <h1 class="text-2xl font-semibold">List of Requests</h1>
                <p class="text-sm text-muted-foreground">
                    Monitor incoming service requests and keep priorities visible.
                </p>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold">Open items</p>
                        <span
                            class="rounded-full bg-[#2563eb]/10 px-2 py-1 text-xs font-semibold text-[#2563eb]"
                        >
                            12
                        </span>
                    </div>
                    <ul class="mt-4 space-y-2 text-sm text-muted-foreground">
                        <li class="rounded-xl bg-muted/40 px-4 py-3">
                            MSO-201 Procurement request review
                        </li>
                        <li class="rounded-xl bg-muted/40 px-4 py-3">
                            MSO-198 Access update for new unit
                        </li>
                        <li class="rounded-xl bg-muted/40 px-4 py-3">
                            MSO-187 Travel request approval
                        </li>
                    </ul>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-sm font-semibold">At a glance</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl bg-muted/40 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                                Pending
                            </p>
                            <p class="mt-2 text-2xl font-semibold">7</p>
                        </div>
                        <div class="rounded-xl bg-muted/40 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                                In Review
                            </p>
                            <p class="mt-2 text-2xl font-semibold">5</p>
                        </div>
                        <div class="rounded-xl bg-muted/40 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                                Assigned
                            </p>
                            <p class="mt-2 text-2xl font-semibold">9</p>
                        </div>
                        <div class="rounded-xl bg-muted/40 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                                Closed
                            </p>
                            <p class="mt-2 text-2xl font-semibold">31</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
