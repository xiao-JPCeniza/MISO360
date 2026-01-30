<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type NatureRequestRow = {
    id: number;
    name: string;
    isActive: boolean;
    usageCount: number;
    updatedAt: string | null;
};

const props = defineProps<{
    requests: NatureRequestRow[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Nature of Request',
        href: '/admin/nature-of-request',
    },
];

const search = ref('');
const editingId = ref<number | null>(null);
const form = useForm({
    name: '',
    is_active: true,
});

const filteredRequests = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) {
        return props.requests;
    }

    return props.requests.filter((request) =>
        request.name.toLowerCase().includes(query),
    );
});

const isEditing = computed(() => editingId.value !== null);

function startCreate() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
}

function startEdit(request: NatureRequestRow) {
    editingId.value = request.id;
    form.name = request.name;
    form.is_active = request.isActive;
    form.clearErrors();
}

function submit() {
    if (isEditing.value && editingId.value !== null) {
        form.patch(`/admin/nature-of-request/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: startCreate,
        });
        return;
    }

    form.post('/admin/nature-of-request', {
        preserveScroll: true,
        onSuccess: startCreate,
    });
}

function remove(request: NatureRequestRow) {
    if (!confirm(`Remove "${request.name}" from the list?`)) {
        return;
    }

    router.delete(`/admin/nature-of-request/${request.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (editingId.value === request.id) {
                startCreate();
            }
        },
    });
}

function restore(request: NatureRequestRow) {
    router.patch(
        `/admin/nature-of-request/${request.id}`,
        {
            name: request.name,
            is_active: true,
        },
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head title="Nature of Request" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <section class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <p class="text-xs uppercase tracking-[0.3em] text-muted-foreground">
                            Administration
                        </p>
                        <h1 class="text-2xl font-semibold">Nature of Request</h1>
                        <p class="text-sm text-muted-foreground">
                            Manage the request types used across service logs and ticket forms.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="rounded-full border border-sidebar-border/60 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                            Total: {{ props.requests.length }}
                        </div>
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search request types..."
                            class="h-10 w-64 rounded-full border border-sidebar-border/70 bg-background px-4 text-sm"
                        />
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">
                            {{ isEditing ? 'Edit request type' : 'Add a request type' }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Keep labels consistent so existing transactions remain intact.
                        </p>
                    </div>
                    <button
                        v-if="isEditing"
                        type="button"
                        class="rounded-full border border-sidebar-border/70 px-4 py-2 text-xs font-semibold text-muted-foreground transition-colors hover:bg-muted/40"
                        @click="startCreate"
                    >
                        Cancel editing
                    </button>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                            Request type name
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="mt-2 w-full rounded-xl border border-sidebar-border/70 bg-background px-3 py-2 text-sm"
                            placeholder="Describe the service request"
                        />
                        <p v-if="form.errors.name" class="mt-2 text-xs text-red-500">
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="h-10 rounded-full bg-[#2563eb] px-6 text-sm font-semibold text-white transition-colors hover:bg-[#1d4ed8] disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="form.processing || !form.name.trim()"
                        @click="submit"
                    >
                        {{ isEditing ? 'Save changes' : 'Add request type' }}
                    </button>
                </div>
            </section>

            <section class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                <h2 class="text-lg font-semibold">Request types</h2>

                <div
                    v-if="!filteredRequests.length"
                    class="mt-6 rounded-2xl border border-dashed border-sidebar-border/70 p-8 text-center text-sm text-muted-foreground"
                >
                    No request types match this search.
                </div>

                <div
                    v-else
                    class="mt-6 overflow-hidden rounded-2xl border border-sidebar-border/60"
                >
                    <div class="hidden grid-cols-[1.6fr_0.5fr_0.5fr_0.7fr] gap-4 border-b border-sidebar-border/60 bg-muted/30 px-4 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground md:grid">
                        <span>Request type</span>
                        <span>Status</span>
                        <span>Usage</span>
                        <span class="text-right">Actions</span>
                    </div>
                    <div class="divide-y divide-sidebar-border/60">
                        <div
                            v-for="request in filteredRequests"
                            :key="request.id"
                            class="flex flex-col gap-3 px-4 py-4 text-sm md:grid md:grid-cols-[1.6fr_0.5fr_0.5fr_0.7fr] md:items-center md:gap-4"
                        >
                            <div>
                                <p class="font-semibold">{{ request.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    Updated
                                    {{ request.updatedAt ? request.updatedAt : '—' }}
                                </p>
                            </div>
                            <span
                                class="inline-flex w-fit items-center rounded-full px-2 py-1 text-xs font-semibold"
                                :class="request.isActive
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200'
                                    : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-200'"
                            >
                                {{ request.isActive ? 'Active' : 'Removed' }}
                            </span>
                            <span class="text-xs text-muted-foreground">
                                {{ request.usageCount }} linked
                            </span>
                            <div class="flex flex-wrap justify-end gap-2">
                                <button
                                    type="button"
                                    class="rounded-full border border-sidebar-border/70 px-3 py-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:bg-muted/40"
                                    @click="startEdit(request)"
                                >
                                    Edit
                                </button>
                                <button
                                    v-if="request.isActive"
                                    type="button"
                                    class="rounded-full border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-50 dark:border-rose-500/40 dark:hover:bg-rose-500/10"
                                    @click="remove(request)"
                                >
                                    Remove
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="rounded-full border border-sidebar-border/70 px-3 py-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:bg-muted/40"
                                    @click="restore(request)"
                                >
                                    Restore
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
