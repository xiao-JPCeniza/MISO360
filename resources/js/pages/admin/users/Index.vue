<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import { toggleAdminVerification as adminVerificationToggle } from '@/actions/App/Http/Controllers/Admin/UserManagementController';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { CheckCircle2, Loader2, XCircle } from 'lucide-vue-next';
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
    admin_verified_at?: string | null;
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

const togglingUserId = ref<number | null>(null);

function roleIsElevated(role: string): boolean {
    return role === 'admin' || role === 'super_admin';
}

function isAccountVerified(row: UserRow): boolean {
    if (roleIsElevated(row.role)) {
        return true;
    }

    return row.admin_verified_at != null && row.admin_verified_at !== '';
}

function submitVerificationToggle(row: UserRow): void {
    if (roleIsElevated(row.role) || togglingUserId.value !== null) {
        return;
    }

    togglingUserId.value = row.id;
    const { url } = adminVerificationToggle.post(row.id);
    router.post(
        url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                togglingUserId.value = null;
            },
            onError: () => {
                togglingUserId.value = null;
            },
        },
    );
}

function verificationToggleTitle(row: UserRow): string {
    if (isAccountVerified(row)) {
        return 'Click to switch to unverified — block access until approved again';
    }

    return 'Click to switch to verified — allow sign-in and app access';
}

function verificationToggleAriaLabel(row: UserRow): string {
    if (isAccountVerified(row)) {
        return `Unverify ${row.name}`;
    }

    return `Verify ${row.name}`;
}
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
                            Manage user access, roles, and account status. For standard accounts, one control shows
                            <span class="font-medium text-emerald-600 dark:text-emerald-300">Verified</span>
                            or
                            <span class="font-medium text-rose-600 dark:text-rose-300">Unverified</span>
                            — click it to switch between the two.
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
                    <div class="hidden grid-cols-[1.2fr_1.2fr_0.55fr_0.55fr_0.5fr_0.4fr] gap-4 border-b border-sidebar-border/60 bg-muted/30 px-4 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground md:grid">
                        <span>User</span>
                        <span>Contact</span>
                        <span>Role</span>
                        <span>Verified</span>
                        <span>Status</span>
                        <span class="text-right">Actions</span>
                    </div>
                    <div class="divide-y divide-sidebar-border/60">
                        <div
                            v-for="user in props.users.data"
                            :key="user.id"
                            class="flex flex-col gap-3 px-4 py-4 text-sm md:grid md:grid-cols-[1.2fr_1.2fr_0.55fr_0.55fr_0.5fr_0.4fr] md:items-center md:gap-4"
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
                            <div class="md:justify-self-start">
                                <button
                                    v-if="!roleIsElevated(user.role)"
                                    type="button"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold shadow-sm transition hover:brightness-95 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 disabled:active:scale-100 dark:hover:brightness-110"
                                    :class="isAccountVerified(user)
                                        ? 'border-emerald-600/25 bg-emerald-100 text-emerald-800 dark:border-emerald-400/30 dark:bg-emerald-500/20 dark:text-emerald-200'
                                        : 'border-rose-600/25 bg-rose-100 text-rose-800 dark:border-rose-400/30 dark:bg-rose-500/20 dark:text-rose-200'"
                                    :disabled="togglingUserId !== null"
                                    :title="verificationToggleTitle(user)"
                                    :aria-label="verificationToggleAriaLabel(user)"
                                    :aria-busy="togglingUserId === user.id"
                                    @click.stop.prevent="submitVerificationToggle(user)"
                                >
                                    <Loader2
                                        v-if="togglingUserId === user.id"
                                        class="size-3.5 shrink-0 animate-spin"
                                        aria-hidden="true"
                                    />
                                    <CheckCircle2
                                        v-else-if="isAccountVerified(user)"
                                        class="size-3.5 shrink-0"
                                        aria-hidden="true"
                                    />
                                    <XCircle
                                        v-else
                                        class="size-3.5 shrink-0"
                                        aria-hidden="true"
                                    />
                                    <span>
                                        {{
                                            togglingUserId === user.id
                                                ? 'Updating'
                                                : isAccountVerified(user)
                                                  ? 'Verified'
                                                  : 'Unverified'
                                        }}
                                    </span>
                                </button>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200"
                                >
                                    <CheckCircle2 class="size-3.5 shrink-0" aria-hidden="true" />
                                    Verified
                                </span>
                            </div>
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
                    >
                        <span v-html="link.label" />
                    </Link>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
