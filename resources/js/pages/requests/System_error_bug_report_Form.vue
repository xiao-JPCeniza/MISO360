<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Requests', href: '/requests' },
    { title: 'System Error / Bug Report Form', href: '/requests/system-error-bug-report' },
];
</script>

<template>
    <Head title="System Error / Bug Report Form" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-6">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                    Request form
                </p>
                <h1 class="text-xl font-semibold text-foreground">
                    System Error / Bug Report
                </h1>
                <p class="text-sm text-muted-foreground">
                    Placeholder page. Wire this to the correct route/controller when ready.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
