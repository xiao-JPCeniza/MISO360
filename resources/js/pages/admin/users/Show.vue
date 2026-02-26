<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type AuditLogEntry = {
    id: number;
    action: string;
    metadata?: Record<string, unknown> | null;
    created_at: string;
};

type OfficeDesignation = {
    id: number;
    name: string;
};

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string | null;
    phone?: string | null;
    role: string;
    is_active: boolean;
    two_factor_enabled: boolean;
    two_factor_confirmed_at?: string | null;
    office_designation_id: number | null;
    position_title?: string | null;
    created_at: string;
    updated_at: string;
};

const props = defineProps<{
    user: ManagedUser;
    officeDesignation: OfficeDesignation | null;
    officeDesignations: OfficeDesignation[];
    roleOptions: Record<string, string>;
    auditLogs: AuditLogEntry[];
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
    {
        title: props.user.name,
        href: `/admin/users/${props.user.id}`,
    },
];

const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone ?? '',
});

const workForm = useForm({
    office_designation_id: props.user.office_designation_id ?? props.officeDesignation?.id ?? null,
    position_title: props.user.position_title ?? '',
});

const roleForm = useForm({
    role: props.user.role,
});

const statusForm = useForm({
    is_active: props.user.is_active,
});

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

const verifyEmailForm = useForm({});
</script>

<template>
    <Head :title="`Manage ${props.user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <section class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-muted-foreground">
                            User management
                        </p>
                        <h1 class="mt-2 text-2xl font-semibold">
                            {{ props.user.name }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ props.user.email }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            2FA:
                            {{ props.user.two_factor_enabled ? 'Enabled' : 'Disabled' }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                            :class="props.user.is_active
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200'
                                : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-200'"
                        >
                            {{ props.user.is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <span class="inline-flex items-center rounded-full bg-muted px-3 py-1 text-xs font-semibold text-foreground">
                            {{ props.roleOptions[props.user.role] || props.user.role }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <div class="space-y-6">
                    <div class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                        <HeadingSmall
                            title="Profile details"
                            description="Update name, email, and contact information."
                        />

                        <form
                            @submit.prevent="profileForm.post(`/admin/users/${props.user.id}`)"
                            class="mt-6 space-y-6"
                        >
                            <div class="grid gap-2">
                                <Label for="name">Name</Label>
                                <Input id="name" v-model="profileForm.name" />
                                <InputError :message="profileForm.errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    v-model="profileForm.email"
                                />
                                <InputError :message="profileForm.errors.email" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="phone">Phone</Label>
                                <Input id="phone" v-model="profileForm.phone" />
                                <InputError :message="profileForm.errors.phone" />
                            </div>
                            <Button :disabled="profileForm.processing">
                                Save profile
                            </Button>
                        </form>
                    </div>

                    <div class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                        <HeadingSmall
                            title="Work details"
                            description="Update designated office and position."
                        />

                        <form
                            @submit.prevent="workForm.post(`/admin/users/${props.user.id}/work`)"
                            class="mt-6 space-y-6"
                        >
                            <div class="grid gap-2">
                                <Label for="office_designation_id">Designated office</Label>
                                <select
                                    id="office_designation_id"
                                    v-model.number="workForm.office_designation_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                >
                                    <option :value="null" disabled>Select an office</option>
                                    <option
                                        v-for="office in props.officeDesignations"
                                        :key="office.id"
                                        :value="office.id"
                                    >
                                        {{ office.name }}
                                    </option>
                                </select>
                                <InputError :message="workForm.errors.office_designation_id" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="position_title">Position / Designation</Label>
                                <Input id="position_title" v-model="workForm.position_title" />
                                <InputError :message="workForm.errors.position_title" />
                            </div>
                            <div class="flex items-center gap-3">
                                <Button :disabled="workForm.processing">
                                    Save work details
                                </Button>
                                <p v-if="workForm.recentlySuccessful" class="text-sm text-emerald-600">
                                    Work details saved.
                                </p>
                            </div>
                        </form>
                    </div>

                    <div class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                        <HeadingSmall
                            title="Access controls"
                            description="Assign roles and update account status."
                        />

                        <form
                            @submit.prevent="roleForm.post(`/admin/users/${props.user.id}/role`)"
                            class="mt-6 space-y-4"
                        >
                            <div class="grid gap-2">
                                <Label for="role">Role</Label>
                                <select
                                    id="role"
                                    v-model="roleForm.role"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                >
                                    <option
                                        v-for="(label, value) in props.roleOptions"
                                        :key="value"
                                        :value="value"
                                    >
                                        {{ label }}
                                    </option>
                                </select>
                                <InputError :message="roleForm.errors.role" />
                            </div>
                            <Button :disabled="roleForm.processing">
                                Update role
                            </Button>
                            <p v-if="roleForm.recentlySuccessful" class="text-sm text-emerald-600">
                                Role updated.
                            </p>
                        </form>

                        <form
                            @submit.prevent="statusForm.post(`/admin/users/${props.user.id}/status`)"
                            class="mt-6 space-y-4"
                        >
                            <div class="flex items-center gap-3 text-sm">
                                <input
                                    id="is_active"
                                    type="checkbox"
                                    v-model="statusForm.is_active"
                                    class="h-4 w-4 rounded border-slate-600 text-sky-500"
                                />
                                <Label for="is_active">Account active</Label>
                            </div>
                            <InputError :message="statusForm.errors.is_active" />
                            <Button
                                variant="secondary"
                                :disabled="statusForm.processing"
                            >
                                Update status
                            </Button>
                            <p v-if="statusForm.recentlySuccessful" class="text-sm text-emerald-600">
                                Status updated.
                            </p>
                        </form>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                        <HeadingSmall
                            title="Password reset"
                            description="Set a new password for this account."
                        />

                        <form
                            @submit.prevent="passwordForm.post(`/admin/users/${props.user.id}/password`)"
                            class="mt-6 space-y-4"
                        >
                            <div class="grid gap-2">
                                <Label for="password">New password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    v-model="passwordForm.password"
                                />
                                <InputError :message="passwordForm.errors.password" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="password_confirmation">Confirm password</Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    v-model="passwordForm.password_confirmation"
                                />
                            </div>
                            <Button
                                variant="secondary"
                                :disabled="passwordForm.processing"
                            >
                                Update password
                            </Button>
                            <p v-if="passwordForm.recentlySuccessful" class="text-sm text-emerald-600">
                                Password updated.
                            </p>
                        </form>

                        <div class="mt-6 border-t border-sidebar-border/60 pt-6">
                            <p class="mb-2 text-sm font-medium text-foreground">
                                Email verification
                            </p>
                            <p class="mb-3 text-sm text-muted-foreground">
                                {{ props.user.email_verified_at
                                    ? 'This account\'s email is verified. The user can sign in without verifying.'
                                    : 'Mark this account\'s email as verified so the user can sign in without following the verification link.' }}
                            </p>
                            <form
                                v-if="!props.user.email_verified_at"
                                @submit.prevent="verifyEmailForm.post(`/admin/users/${props.user.id}/verify-email`)"
                                class="inline"
                            >
                                <Button
                                    type="submit"
                                    variant="outline"
                                    :disabled="verifyEmailForm.processing"
                                >
                                    {{ verifyEmailForm.processing ? 'Verifying…' : 'Force verify email' }}
                                </Button>
                            </form>
                            <p
                                v-else
                                class="text-sm text-emerald-600"
                            >
                                Email verified
                            </p>
                            <p v-if="verifyEmailForm.recentlySuccessful" class="mt-2 text-sm text-emerald-600">
                                Email marked as verified.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                        <HeadingSmall
                            title="Audit trail"
                            description="Recent actions taken for this account."
                        />
                        <div v-if="!props.auditLogs.length" class="mt-4 text-sm text-muted-foreground">
                            No audit activity recorded yet.
                        </div>
                        <div v-else class="mt-4 space-y-3 text-sm">
                            <div
                                v-for="log in props.auditLogs"
                                :key="log.id"
                                class="rounded-xl border border-sidebar-border/60 px-4 py-3 text-xs text-muted-foreground"
                            >
                                <p class="font-semibold text-foreground">
                                    {{ log.action }}
                                </p>
                                <p class="mt-1">{{ log.created_at }}</p>
                            </div>
                        </div>
                        <Link
                            href="/admin/audit-logs"
                            class="mt-4 inline-flex text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground hover:text-foreground"
                        >
                            View full audit log
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
