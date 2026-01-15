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
        title: 'Inventory',
        href: '/inventory',
    },
];
</script>

<template>
    <Head title="Inventory" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <div class="space-y-2">
                <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                    Inventory
                </p>
                <h1 class="text-2xl font-semibold">Inventory</h1>
                <p class="text-sm text-muted-foreground">
                    Track supplies and equipment availability across teams.
                </p>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        Critical Stock
                    </p>
                    <p class="mt-4 text-3xl font-semibold">6</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Items below restock threshold.
                    </p>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        On Order
                    </p>
                    <p class="mt-4 text-3xl font-semibold">18</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Orders expected in the next two weeks.
                    </p>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        Available
                    </p>
                    <p class="mt-4 text-3xl font-semibold">142</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Items ready for allocation.
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-sidebar-border/60 bg-background p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-lg font-semibold">Recent updates</h2>
                    <p class="text-sm text-muted-foreground">
                        Updated within the last 7 days.
                    </p>
                </div>
                <div class="mt-4 grid gap-3 text-sm text-muted-foreground">
                    <div class="rounded-xl bg-muted/40 px-4 py-3">
                        Laptop inventory audit completed.
                    </div>
                    <div class="rounded-xl bg-muted/40 px-4 py-3">
                        Replenish printer supplies for Unit 4.
                    </div>
                    <div class="rounded-xl bg-muted/40 px-4 py-3">
                        New ergonomic chairs delivered to MSO floor.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
