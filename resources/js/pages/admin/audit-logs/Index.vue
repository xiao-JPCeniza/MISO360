<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type Paginated<T> = {
    data: T[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    total: number;
};

type AuditLogEntry = {
    id: number;
    action: string;
    target_type: string;
    target_id?: number | null;
    metadata?: Record<string, unknown> | null;
    ip_address?: string | null;
    created_at: string;
    actor?: {
        id: number;
        name: string;
        email: string;
        role: string;
    } | null;
};

const props = defineProps<{
    logs: Paginated<AuditLogEntry>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Audit logs',
        href: '/admin/audit-logs',
    },
];
</script>

<template>
    <Head title="Audit logs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <section class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <p class="text-xs uppercase tracking-[0.3em] text-muted-foreground">
                            Governance
                        </p>
                        <h1 class="text-2xl font-semibold">Audit logs</h1>
                        <p class="text-sm text-muted-foreground">
                            Review role changes and account activity for compliance.
                        </p>
                    </div>
                    <div class="rounded-full border border-sidebar-border/60 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                        Entries: {{ props.logs.total }}
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                <div v-if="!props.logs.data.length" class="rounded-2xl border border-dashed border-sidebar-border/70 p-8 text-center text-sm text-muted-foreground">
                    No audit entries recorded.
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="entry in props.logs.data"
                        :key="entry.id"
                        class="rounded-2xl border border-sidebar-border/60 px-4 py-4 text-sm"
                    >
                        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="font-semibold">{{ entry.action }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ entry.created_at }}
                                </p>
                            </div>
                            <div class="text-xs text-muted-foreground">
                                <p>
                                    Actor:
                                    <span class="font-semibold text-foreground">
                                        {{ entry.actor?.name || 'System' }}
                                    </span>
                                </p>
                                <p>
                                    Target:
                                    <span class="font-semibold text-foreground">
                                        {{ entry.target_type }}
                                        <span v-if="entry.target_id">#{{ entry.target_id }}</span>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="props.logs.links.length" class="mt-6 flex flex-wrap justify-center gap-2">
                    <Link
                        v-for="link in props.logs.links"
                        :key="link.label"
                        :href="link.url || ''"
                        class="rounded-full border px-3 py-1 text-xs font-semibold"
                        :class="[
                            link.active
                                ? 'border-transparent bg-[#0f172a] text-white'
                                : 'border-sidebar-border/60 text-muted-foreground hover:bg-muted/40',
                            !link.url ? 'pointer-events-none opacity-50' : '',
                        ]"
                    >
                        <span v-html="link.label" />
                    </Link>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
