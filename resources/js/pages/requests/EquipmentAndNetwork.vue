<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type SelectOption = {
    id: number | string;
    name: string;
};

type Attachment = {
    name: string;
    url?: string | null;
};

type TicketDetails = {
    controlTicketNumber: string;
    requestedBy: string | null;
    positionTitle: string | null;
    office: string | null;
    dateFiled: string | null;
    email: string | null;
    natureOfRequest: string | null;
    requestDescription: string | null;
    attachments: Attachment[];
    remarksId?: number | string | null;
    assignedStaffId?: number | string | null;
    dateReceived?: string | null;
    dateStarted?: string | null;
    estimatedCompletionDate?: string | null;
    actionTaken?: string | null;
    categoryId?: number | string | null;
    statusId?: number | string | null;
};

const props = defineProps<{
    ticket: TicketDetails;
    updateUrl: string;
    staffOptions: SelectOption[];
    remarksOptions?: SelectOption[];
    categoryOptions?: SelectOption[];
    statusOptions?: SelectOption[];
    canEdit: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Requests',
        href: '/requests',
    },
    {
        title: 'Equipment and Network',
        href: '/requests',
    },
];

const fallbackRemarks: SelectOption[] = [
    { id: 'Pick-up', name: 'Pick-up' },
    { id: 'To Deliver', name: 'To Deliver' },
    { id: 'Remote', name: 'Remote' },
];
const fallbackCategories: SelectOption[] = [
    { id: 'Simple', name: 'Simple' },
    { id: 'Complex', name: 'Complex' },
    { id: 'Urgent', name: 'Urgent' },
];
const fallbackStatuses: SelectOption[] = [
    { id: 'Pending', name: 'Pending' },
    { id: 'Ongoing', name: 'Ongoing' },
    { id: 'Completed', name: 'Completed' },
];

const remarksList = computed(() =>
    props.remarksOptions?.length ? props.remarksOptions : fallbackRemarks,
);
const categoryList = computed(() =>
    props.categoryOptions?.length ? props.categoryOptions : fallbackCategories,
);
const statusList = computed(() =>
    props.statusOptions?.length ? props.statusOptions : fallbackStatuses,
);
const attachments = computed(() => props.ticket.attachments ?? []);
const isEditable = computed(() => props.canEdit);

type FormFields = {
    remarksId: number | string;
    assignedStaffId: number | string;
    dateReceived: string;
    dateStarted: string;
    estimatedCompletionDate: string;
    actionTaken: string;
    categoryId: number | string;
    statusId: number | string;
};

type FieldName = keyof FormFields;

const form = useForm<FormFields>({
    remarksId: props.ticket.remarksId != null ? String(props.ticket.remarksId) : '',
    assignedStaffId: props.ticket.assignedStaffId != null ? String(props.ticket.assignedStaffId) : '',
    dateReceived: props.ticket.dateReceived ?? '',
    dateStarted: props.ticket.dateStarted ?? '',
    estimatedCompletionDate: props.ticket.estimatedCompletionDate ?? '',
    actionTaken: props.ticket.actionTaken ?? '',
    categoryId: props.ticket.categoryId != null ? String(props.ticket.categoryId) : '',
    statusId: props.ticket.statusId != null ? String(props.ticket.statusId) : '',
});

const localErrors = ref<Partial<Record<FieldName, string>>>({});

const fieldError = (field: FieldName) => form.errors[field] ?? localErrors.value[field] ?? '';

const dateIsAfter = (start: string, end: string) => start && end && start > end;

function validateForm() {
    localErrors.value = {};

    if (!form.remarksId) {
        localErrors.value.remarksId = 'Remarks is required.';
    }
    if (!form.assignedStaffId) {
        localErrors.value.assignedStaffId = 'Assigned IT staff is required.';
    }
    if (!form.categoryId) {
        localErrors.value.categoryId = 'Category is required.';
    }
    if (!form.statusId) {
        localErrors.value.statusId = 'Status is required.';
    }

    if (form.dateReceived && form.dateStarted && dateIsAfter(form.dateReceived, form.dateStarted)) {
        localErrors.value.dateStarted = 'Date started must be on or after date received.';
    }
    if (
        form.dateStarted &&
        form.estimatedCompletionDate &&
        dateIsAfter(form.dateStarted, form.estimatedCompletionDate)
    ) {
        localErrors.value.estimatedCompletionDate =
            'Estimated completion must be on or after date started.';
    }

    return Object.keys(localErrors.value).length === 0;
}

function submitForm() {
    if (!isEditable.value) {
        return;
    }

    form.clearErrors();

    if (!validateForm()) {
        return;
    }

    form.patch(props.updateUrl, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Equipment and Network" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="-mx-6 -mt-20 min-h-screen bg-[#0b2e59] text-white">
            <form
                class="mx-auto flex w-full max-w-5xl flex-col gap-0 px-6 pb-12 pt-6"
                @submit.prevent="submitForm"
            >
                <h1 class="mb-4 text-lg font-semibold tracking-tight text-white">
                    Equipment and Network
                </h1>

                <div class="rounded-lg border border-white/15 bg-white/5 px-4 py-3 shadow-sm">
                    <h2 class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-white/70">
                        Ticket info
                    </h2>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Control Ticket No.
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="props.ticket.controlTicketNumber ?? ''"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Requested By
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="props.ticket.requestedBy ?? ''"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Position/Designation
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="props.ticket.positionTitle ?? ''"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Office
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="props.ticket.office ?? ''"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Date Filed
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="props.ticket.dateFiled ?? ''"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Email
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="props.ticket.email ?? ''"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Nature of Request
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="props.ticket.natureOfRequest ?? ''"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Request Description
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="props.ticket.requestDescription ?? ''"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-lg border border-white/15 bg-white/5 px-4 py-3 shadow-sm">
                    <h2 class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-white/70">
                        IT processing
                    </h2>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Remarks
                            </label>
                            <select
                                v-model="form.remarksId"
                                class="h-8 w-full appearance-none rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            >
                                <option value="" disabled>Select remark</option>
                                <option v-for="option in remarksList" :key="option.id" :value="String(option.id)">
                                    {{ option.name }}
                                </option>
                            </select>
                            <p v-if="fieldError('remarksId')" class="mt-0.5 text-[10px] text-red-300">
                                {{ fieldError('remarksId') }}
                            </p>
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Assigned IT Staff
                            </label>
                            <select
                                v-model="form.assignedStaffId"
                                class="h-8 w-full appearance-none rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            >
                                <option value="" disabled>Select staff</option>
                                <option v-for="staff in props.staffOptions" :key="staff.id" :value="String(staff.id)">
                                    {{ staff.name }}
                                </option>
                            </select>
                            <p v-if="fieldError('assignedStaffId')" class="mt-0.5 text-[10px] text-red-300">
                                {{ fieldError('assignedStaffId') }}
                            </p>
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Date Received
                            </label>
                            <input
                                v-model="form.dateReceived"
                                type="date"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Date Started
                            </label>
                            <input
                                v-model="form.dateStarted"
                                type="date"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            />
                            <p v-if="fieldError('dateStarted')" class="mt-0.5 text-[10px] text-red-300">
                                {{ fieldError('dateStarted') }}
                            </p>
                        </div>
                        <div class="grid gap-0.5 sm:col-span-2 lg:col-span-1">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Estimated Completion Date
                            </label>
                            <input
                                v-model="form.estimatedCompletionDate"
                                type="date"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            />
                            <p v-if="fieldError('estimatedCompletionDate')" class="mt-0.5 text-[10px] text-red-300">
                                {{ fieldError('estimatedCompletionDate') }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-3 grid gap-0.5">
                        <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                            Action Taken
                        </label>
                        <input
                            v-model="form.actionTaken"
                            type="text"
                            class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                            :disabled="!isEditable"
                            placeholder="Add notes…"
                        />
                    </div>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Attachments
                            </label>
                            <div class="flex min-h-8 flex-wrap items-center gap-1.5 rounded border border-white/20 bg-slate-100/80 px-2 py-1.5">
                                <template v-if="attachments.length">
                                    <a
                                        v-for="attachment in attachments"
                                        :key="attachment.name"
                                        :href="attachment.url || undefined"
                                        :target="attachment.url ? '_blank' : undefined"
                                        :rel="attachment.url ? 'noreferrer' : undefined"
                                        class="inline-flex items-center rounded bg-white/90 px-2 py-0.5 text-[10px] font-medium text-slate-600 shadow-sm transition hover:bg-white"
                                    >
                                        {{ attachment.name }}
                                    </a>
                                </template>
                                <span v-else class="text-[10px] text-slate-400">None</span>
                            </div>
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Category
                            </label>
                            <select
                                v-model="form.categoryId"
                                class="h-8 w-full appearance-none rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            >
                                <option value="" disabled>Choose category</option>
                                <option v-for="option in categoryList" :key="option.id" :value="String(option.id)">
                                    {{ option.name }}
                                </option>
                            </select>
                            <p v-if="fieldError('categoryId')" class="mt-0.5 text-[10px] text-red-300">
                                {{ fieldError('categoryId') }}
                            </p>
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Status
                            </label>
                            <select
                                v-model="form.statusId"
                                class="h-8 w-full appearance-none rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            >
                                <option value="" disabled>Choose status</option>
                                <option v-for="option in statusList" :key="option.id" :value="String(option.id)">
                                    {{ option.name }}
                                </option>
                            </select>
                            <p v-if="fieldError('statusId')" class="mt-0.5 text-[10px] text-red-300">
                                {{ fieldError('statusId') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-lg border border-white/20 bg-white/[0.07] px-4 py-3 shadow-sm">
                    <h2 class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-white/70">
                        Equipment / Network Details (IT Use)
                    </h2>
                    <p class="mb-3 text-[9px] italic text-white/60">
                        For network-related requests, additional fields shall be accomplished by IT staff.
                    </p>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                RJ45
                            </label>
                            <input
                                type="text"
                                placeholder="No. of pcs"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Fiber Optic – Heat Shrink Sleeve
                            </label>
                            <input
                                type="text"
                                placeholder="No. of pcs"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Fiber Optic S-Clamp
                            </label>
                            <input
                                type="text"
                                placeholder="No. of pcs"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                SC Connector
                            </label>
                            <input
                                type="text"
                                placeholder="No. of pcs"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                NAP Box
                            </label>
                            <input
                                type="text"
                                placeholder="No. of pcs"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                            />
                        </div>
                    </div>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Fiber Optic
                            </label>
                            <div class="flex gap-1.5">
                                <input
                                    type="text"
                                    placeholder="Meters"
                                    class="h-8 flex-1 min-w-0 rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                                <input
                                    type="text"
                                    placeholder="Type"
                                    class="h-8 w-20 rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                            </div>
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                UTP Cable
                            </label>
                            <div class="flex gap-1.5">
                                <input
                                    type="text"
                                    placeholder="Meters"
                                    class="h-8 flex-1 min-w-0 rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                                <input
                                    type="text"
                                    placeholder="Type"
                                    class="h-8 w-20 rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                WiFi Router / UNO
                            </label>
                            <div class="flex flex-wrap gap-1.5">
                                <input
                                    type="text"
                                    placeholder="Qty"
                                    class="h-8 w-14 rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                                <input
                                    type="text"
                                    placeholder="Brand"
                                    class="h-8 flex-1 min-w-0 rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                                <input
                                    type="text"
                                    placeholder="Serial"
                                    class="h-8 flex-1 min-w-0 rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                                <input
                                    type="text"
                                    placeholder="Model"
                                    class="h-8 flex-1 min-w-0 rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                            </div>
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Network Switch
                            </label>
                            <div class="flex flex-wrap gap-1.5">
                                <input
                                    type="text"
                                    placeholder="Qty"
                                    class="h-8 w-14 rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                                <input
                                    type="text"
                                    placeholder="Brand"
                                    class="h-8 flex-1 min-w-0 rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                                <input
                                    type="text"
                                    placeholder="Serial"
                                    class="h-8 flex-1 min-w-0 rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                                <input
                                    type="text"
                                    placeholder="Model"
                                    class="h-8 flex-1 min-w-0 rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                            </div>
                        </div>
                        <div class="grid gap-0.5 sm:col-span-2 lg:col-span-1">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                AP Beam
                            </label>
                            <div class="flex flex-wrap gap-1.5">
                                <input
                                    type="text"
                                    placeholder="Qty"
                                    class="h-8 w-14 rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                                <input
                                    type="text"
                                    placeholder="Brand"
                                    class="h-8 flex-1 min-w-0 rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                                <input
                                    type="text"
                                    placeholder="Serial"
                                    class="h-8 flex-1 min-w-0 rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                                <input
                                    type="text"
                                    placeholder="Model"
                                    class="h-8 flex-1 min-w-0 rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-col items-end gap-1.5">
                    <button
                        v-if="isEditable"
                        type="submit"
                        class="rounded-md bg-blue-500 px-6 py-2 text-xs font-semibold uppercase tracking-wider text-white shadow-md transition hover:bg-blue-400 disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:bg-blue-500"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving…' : 'Save' }}
                    </button>
                    <p v-if="form.recentlySuccessful" class="text-[10px] text-emerald-300">
                        Updates saved successfully.
                    </p>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
