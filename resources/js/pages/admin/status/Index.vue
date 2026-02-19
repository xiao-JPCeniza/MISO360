<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type GroupKey = 'status' | 'category' | 'officeDesignation' | 'remarks';

type ReferenceRow = {
    id: number;
    name: string;
    isActive: boolean;
    usageCount: number;
    updatedAt: string | null;
};

type GroupConfig = {
    key: GroupKey;
    title: string;
    description: string;
    placeholder: string;
    emptyLabel: string;
};

const props = defineProps<{
    groups: Record<GroupKey, ReferenceRow[]>;
}>();

const page = usePage();
const flashSuccess = computed(() => (page.props.flash as { status?: string })?.status ?? null);
const flashError = computed(() => (page.props.flash as { error?: string })?.error ?? null);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Status Management',
        href: '/admin/status',
    },
];

const groupConfigs: GroupConfig[] = [
    {
        key: 'status',
        title: 'Status',
        description: 'Control the lifecycle labels used across request tracking.',
        placeholder: 'Pending, Ongoing, Completed',
        emptyLabel: 'No statuses match this search.',
    },
    {
        key: 'category',
        title: 'Category',
        description: 'Define asset categories that appear in ticket enrollments.',
        placeholder: 'Simple, Complex, Urgent',
        emptyLabel: 'No categories match this search.',
    },
    {
        key: 'officeDesignation',
        title: 'Office Designation',
        description: 'Set the office or division identifiers used in assignments.',
        placeholder: 'Add office or division',
        emptyLabel: 'No office designations match this search.',
    },
    {
        key: 'remarks',
        title: 'Remarks',
        description: 'Standardize commonly used remarks for requests.',
        placeholder: 'For Pickup, To Deliver, Remote',
        emptyLabel: 'No remarks match this search.',
    },
];

const search = reactive<Record<GroupKey, string>>({
    status: '',
    category: '',
    officeDesignation: '',
    remarks: '',
});

const editingId = reactive<Record<GroupKey, number | null>>({
    status: null,
    category: null,
    officeDesignation: null,
    remarks: null,
});

const forms = {
    status: useForm({ name: '', is_active: true, group_key: 'status' }),
    category: useForm({ name: '', is_active: true, group_key: 'category' }),
    officeDesignation: useForm({
        name: '',
        is_active: true,
        group_key: 'office_designation',
    }),
    remarks: useForm({ name: '', is_active: true, group_key: 'remarks' }),
};

const totals = computed(() =>
    groupConfigs.reduce(
        (acc, group) => ({ ...acc, [group.key]: props.groups[group.key].length }),
        {} as Record<GroupKey, number>,
    ),
);

function filteredRows(groupKey: GroupKey) {
    const query = search[groupKey].trim().toLowerCase();

    if (!query) {
        return props.groups[groupKey];
    }

    return props.groups[groupKey].filter((row) =>
        row.name.toLowerCase().includes(query),
    );
}

function isEditing(groupKey: GroupKey) {
    return editingId[groupKey] !== null;
}

function startCreate(groupKey: GroupKey) {
    editingId[groupKey] = null;
    forms[groupKey].reset();
    forms[groupKey].clearErrors();
}

function startEdit(groupKey: GroupKey, row: ReferenceRow) {
    editingId[groupKey] = row.id;
    forms[groupKey].name = row.name;
    forms[groupKey].is_active = row.isActive;
    forms[groupKey].clearErrors();
}

function submit(groupKey: GroupKey) {
    const form = forms[groupKey];
    const editId = editingId[groupKey];

    if (editId) {
        form.patch(`/admin/status/${editId}`, {
            preserveScroll: true,
            onSuccess: () => startCreate(groupKey),
        });
        return;
    }

    form.post('/admin/status', {
        preserveScroll: true,
        onSuccess: () => startCreate(groupKey),
    });
}

function remove(groupKey: GroupKey, row: ReferenceRow) {
    if (!confirm(`Remove "${row.name}" from ${labelFor(groupKey)}?`)) {
        return;
    }

    router.delete(`/admin/status/${row.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (editingId[groupKey] === row.id) {
                startCreate(groupKey);
            }
        },
    });
}

function restore(groupKey: GroupKey, row: ReferenceRow) {
    router.patch(`/admin/status/${row.id}`, {
        name: row.name,
        is_active: true,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            if (editingId[groupKey] === row.id) {
                startCreate(groupKey);
            }
        },
    });
}

function labelFor(groupKey: GroupKey): string {
    return groupConfigs.find((group) => group.key === groupKey)?.title ?? 'this group';
}
</script>

<template>
    <Head title="Status Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <section class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                <div class="space-y-2">
                    <p class="text-xs uppercase tracking-[0.3em] text-muted-foreground">
                        Administration
                    </p>
                    <h1 class="text-2xl font-semibold">Status Management</h1>
                    <p class="text-sm text-muted-foreground">
                        Centralize the status, category, office designation, and remark
                        options used throughout the platform.
                    </p>
                </div>
                <div
                    v-if="flashSuccess"
                    class="mt-4 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-emerald-200"
                    role="status"
                >
                    <span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500" />
                    {{ flashSuccess }}
                </div>
                <div
                    v-if="flashError"
                    class="mt-4 flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/15 dark:text-rose-200"
                    role="alert"
                >
                    <span class="h-2 w-2 shrink-0 rounded-full bg-rose-500" />
                    {{ flashError }}
                </div>
            </section>

            <section
                v-for="group in groupConfigs"
                :key="group.key"
                class="rounded-3xl border border-sidebar-border/60 bg-background p-6"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <h2 class="text-lg font-semibold">{{ group.title }}</h2>
                        <p class="text-sm text-muted-foreground">
                            {{ group.description }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div
                            class="rounded-full border border-sidebar-border/60 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground"
                        >
                            Total: {{ totals[group.key] ?? 0 }}
                        </div>
                        <input
                            v-model="search[group.key]"
                            type="search"
                            :placeholder="`Search ${group.title.toLowerCase()}...`"
                            class="h-10 w-64 rounded-full border border-sidebar-border/70 bg-background px-4 text-sm"
                        />
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border border-sidebar-border/60 bg-muted/10 p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold">
                                {{ isEditing(group.key) ? 'Edit value' : 'Add a value' }}
                            </h3>
                            <p class="text-xs text-muted-foreground">
                                Updates instantly refresh the linked workflows and reports.
                            </p>
                        </div>
                        <button
                            v-if="isEditing(group.key)"
                            type="button"
                            class="rounded-full border border-sidebar-border/70 px-4 py-2 text-xs font-semibold text-muted-foreground transition-colors hover:bg-muted/40"
                            @click="startCreate(group.key)"
                        >
                            Cancel editing
                        </button>
                    </div>

                    <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                {{ group.title }} value
                            </label>
                            <input
                                v-model="forms[group.key].name"
                                type="text"
                                class="mt-2 w-full rounded-xl border border-sidebar-border/70 bg-background px-3 py-2 text-sm"
                                :placeholder="group.placeholder"
                            />
                            <p
                                v-if="forms[group.key].errors.name"
                                class="mt-2 text-xs text-red-500"
                            >
                                {{ forms[group.key].errors.name }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="h-10 rounded-full bg-[#2563eb] px-6 text-sm font-semibold text-white transition-colors hover:bg-[#1d4ed8] disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="
                                forms[group.key].processing ||
                                !forms[group.key].name.trim()
                            "
                            @click="submit(group.key)"
                        >
                            {{ isEditing(group.key) ? 'Save changes' : 'Add value' }}
                        </button>
                    </div>
                </div>

                <div class="mt-6">
                    <div
                        v-if="!filteredRows(group.key).length"
                        class="rounded-2xl border border-dashed border-sidebar-border/70 p-6 text-center text-sm text-muted-foreground"
                    >
                        {{ group.emptyLabel }}
                    </div>
                    <div
                        v-else
                        class="overflow-hidden rounded-2xl border border-sidebar-border/60"
                    >
                        <div
                            class="hidden grid-cols-[1.6fr_0.5fr_0.5fr_0.7fr] gap-4 border-b border-sidebar-border/60 bg-muted/30 px-4 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground md:grid"
                        >
                            <span>Value</span>
                            <span>Status</span>
                            <span>Usage</span>
                            <span class="text-right">Actions</span>
                        </div>
                        <div class="divide-y divide-sidebar-border/60">
                            <div
                                v-for="row in filteredRows(group.key)"
                                :key="row.id"
                                class="flex flex-col gap-3 px-4 py-4 text-sm md:grid md:grid-cols-[1.6fr_0.5fr_0.5fr_0.7fr] md:items-center md:gap-4"
                            >
                                <div>
                                    <p class="font-semibold">{{ row.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        Updated {{ row.updatedAt ? row.updatedAt : '—' }}
                                    </p>
                                </div>
                                <span
                                    class="inline-flex w-fit items-center rounded-full px-2 py-1 text-xs font-semibold"
                                    :class="row.isActive
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200'
                                        : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-200'"
                                >
                                    {{ row.isActive ? 'Active' : 'Removed' }}
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    {{ row.usageCount }} linked
                                </span>
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button
                                        type="button"
                                        class="rounded-full border border-sidebar-border/70 px-3 py-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:bg-muted/40"
                                        @click="startEdit(group.key, row)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        v-if="row.isActive"
                                        type="button"
                                        class="rounded-full border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-50 dark:border-rose-500/40 dark:hover:bg-rose-500/10"
                                        @click="remove(group.key, row)"
                                    >
                                        Remove
                                    </button>
                                    <button
                                        v-else
                                        type="button"
                                        class="rounded-full border border-sidebar-border/70 px-3 py-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:bg-muted/40"
                                        @click="restore(group.key, row)"
                                    >
                                        Restore
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
