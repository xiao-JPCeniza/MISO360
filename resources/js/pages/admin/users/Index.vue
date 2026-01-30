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

type UserRow = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    role: string;
    is_active: boolean;
    two_factor_enabled: boolean;
    created_at: string;
};

const props = defineProps<{
    users: Paginated<UserRow>;
    roleOptions: Record<string, string>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'User management',
        href: '/admin/users',
    },
];
</script>

<template>
    <Head title="User management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <section class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <p class="text-xs uppercase tracking-[0.3em] text-muted-foreground">
                            Administration
                        </p>
                        <h1 class="text-2xl font-semibold">User management</h1>
                        <p class="text-sm text-muted-foreground">
                            Manage user access, roles, and account status.
                        </p>
                    </div>
                    <div class="rounded-full border border-sidebar-border/60 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                        Total users: {{ props.users.total }}
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Users</h2>
                </div>

                <div v-if="!props.users.data.length" class="mt-6 rounded-2xl border border-dashed border-sidebar-border/70 p-8 text-center text-sm text-muted-foreground">
                    No users found.
                </div>

                <div v-else class="mt-6 overflow-hidden rounded-2xl border border-sidebar-border/60">
                    <div class="hidden grid-cols-[1.2fr_1.2fr_0.6fr_0.6fr_0.4fr] gap-4 border-b border-sidebar-border/60 bg-muted/30 px-4 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground md:grid">
                        <span>User</span>
                        <span>Contact</span>
                        <span>Role</span>
                        <span>Status</span>
                        <span class="text-right">Actions</span>
                    </div>
                    <div class="divide-y divide-sidebar-border/60">
                        <div
                            v-for="user in props.users.data"
                            :key="user.id"
                            class="flex flex-col gap-3 px-4 py-4 text-sm md:grid md:grid-cols-[1.2fr_1.2fr_0.6fr_0.6fr_0.4fr] md:items-center md:gap-4"
                        >
                            <div>
                                <p class="font-semibold">{{ user.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                            </div>
                            <div class="text-xs text-muted-foreground">
                                <p>{{ user.phone || 'No phone on file' }}</p>
                                <p>
                                    2FA
                                    <span class="font-semibold">{{
                                        user.two_factor_enabled ? 'Enabled' : 'Disabled'
                                    }}</span>
                                </p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-muted px-2 py-1 text-xs font-semibold text-foreground">
                                {{ props.roleOptions[user.role] || user.role }}
                            </span>
                            <span
                                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold"
                                :class="user.is_active
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200'
                                    : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-200'"
                            >
                                {{ user.is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <div class="flex justify-end">
                                <Link
                                    :href="`/admin/users/${user.id}`"
                                    class="rounded-full border border-sidebar-border/70 px-3 py-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:bg-muted/40"
                                >
                                    Manage
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="props.users.links.length" class="mt-6 flex flex-wrap justify-center gap-2">
                    <Link
                        v-for="link in props.users.links"
                        :key="link.label"
                        :href="link.url || ''"
                        class="rounded-full border px-3 py-1 text-xs font-semibold"
                        :class="[
                            link.active
                                ? 'border-transparent bg-[#0f172a] text-white'
                                : 'border-sidebar-border/60 text-muted-foreground hover:bg-muted/40',
                            !link.url ? 'pointer-events-none opacity-50' : '',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </section>
        </div>
    </AppLayout>
</template>
