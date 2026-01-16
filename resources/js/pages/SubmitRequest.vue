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
        title: 'Submit a Request Ticket',
        href: '/submit-request',
    },
];
</script>

<template>
    <Head title="Submit a Request Ticket" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <div class="space-y-2">
                <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                    Request Ticket
                </p>
                <h1 class="text-2xl font-semibold">Submit a Request Ticket</h1>
                <p class="text-sm text-muted-foreground">
                    Submit a new request ticket for assistance or support.
                </p>
            </div>

            <div class="rounded-2xl border border-sidebar-border/60 bg-background p-6">
                <p class="text-center text-muted-foreground">
                    Request ticket submission form will be implemented here.
                </p>
            </div>
        </div>
    </AppLayout>
</template>