<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import Icon from '@/components/Icon.vue';
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

type SystemIssueReportAttachment = {
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
    natureOfRequestId?: number | string | null;
    requestDescription: string | null;
    attachments: Attachment[];
    systemDevelopmentSurvey?: Record<string, unknown> | null;
    systemChangeRequestForm?: Record<string, unknown> | null;
    systemIssueReport?: Record<string, unknown> | null;
    systemIssueReportAttachments?: SystemIssueReportAttachment[];
    remarksId?: number | string | null;
    assignedStaffId?: number | string | null;
    dateReceived?: string | null;
    dateStarted?: string | null;
    estimatedCompletionDate?: string | null;
    actionTaken?: string | null;
    categoryId?: number | string | null;
    statusId?: number | string | null;
    hasQrCode?: boolean;
    qrCodeNumber?: string | null;
    qrCodePattern?: string;
    validateQrUrl?: string;
    generateQrUrl?: string;
    inventoryEditUrl?: string | null;
};

const props = defineProps<{
    ticket: TicketDetails;
    updateUrl: string;
    natureOfRequests?: SelectOption[];
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
        title: 'Request',
        href: '/requests',
    },
];

const fallbackRemarks: SelectOption[] = [
    { id: 'For Pickup', name: 'For Pickup' },
    { id: 'To Deliver', name: 'To Deliver' },
    { id: 'Remote', name: 'Remote' },
    { id: 'Interview', name: 'Interview' },
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
const systemDevelopmentSurvey = computed(() => props.ticket.systemDevelopmentSurvey ?? null);
const systemChangeRequestForm = computed(() => props.ticket.systemChangeRequestForm ?? null);
const systemIssueReport = computed(() => props.ticket.systemIssueReport ?? null);
const systemIssueReportAttachments = computed(() => props.ticket.systemIssueReportAttachments ?? []);
const isSystemDevelopment = computed(() => {
    return (props.ticket.natureOfRequest ?? '').trim().toLowerCase() === 'system development';
});
const isSystemModification = computed(() => {
    return (props.ticket.natureOfRequest ?? '').trim().toLowerCase() === 'system modification';
});
const isSystemErrorBugReport = computed(() => {
    return (props.ticket.natureOfRequest ?? '').trim().toLowerCase() === 'system error / bug report';
});
const isEditable = computed(() => props.canEdit);
const natureList = computed(() => props.natureOfRequests ?? []);

type FormFields = {
    natureOfRequestId: number | string;
    remarksId: number | string;
    assignedStaffId: number | string;
    dateReceived: string;
    dateStarted: string;
    estimatedCompletionDate: string;
    actionTaken: string;
    categoryId: number | string;
    statusId: number | string;
    systemDevelopmentSurvey: {
        targetCompletion: string;
        assignedSystemsEngineer: string;
    };
    systemChangeRequestForm: {
        evaluatedBy: string;
        approvedBy: string;
        notedBy: string;
        remarks: string;
    };
};

type FieldName = keyof FormFields;

const form = useForm<FormFields>({
    natureOfRequestId:
        props.ticket.natureOfRequestId != null ? String(props.ticket.natureOfRequestId) : '',
    remarksId: props.ticket.remarksId != null ? String(props.ticket.remarksId) : '',
    assignedStaffId: props.ticket.assignedStaffId != null ? String(props.ticket.assignedStaffId) : '',
    dateReceived: props.ticket.dateReceived ?? '',
    dateStarted: props.ticket.dateStarted ?? '',
    estimatedCompletionDate: props.ticket.estimatedCompletionDate ?? '',
    actionTaken: props.ticket.actionTaken ?? '',
    categoryId: props.ticket.categoryId != null ? String(props.ticket.categoryId) : '',
    statusId: props.ticket.statusId != null ? String(props.ticket.statusId) : '',
    systemDevelopmentSurvey: {
        targetCompletion: String(systemDevelopmentSurvey.value?.targetCompletion ?? ''),
        assignedSystemsEngineer: String(systemDevelopmentSurvey.value?.assignedSystemsEngineer ?? ''),
    },
    systemChangeRequestForm: {
        evaluatedBy: String(systemChangeRequestForm.value?.evaluatedBy ?? ''),
        approvedBy: String(systemChangeRequestForm.value?.approvedBy ?? ''),
        notedBy: String(systemChangeRequestForm.value?.notedBy ?? ''),
        remarks: String(systemChangeRequestForm.value?.remarks ?? ''),
    },
    systemIssueReport: {
        reportedBy: String(systemIssueReport.value?.reportedBy ?? ''),
        reportedByDate: String(systemIssueReport.value?.reportedByDate ?? ''),
        reportedBySignature: String(systemIssueReport.value?.reportedBySignature ?? ''),
        acceptedBy: String(systemIssueReport.value?.acceptedBy ?? ''),
        acceptedByDate: String(systemIssueReport.value?.acceptedByDate ?? ''),
        acceptedBySignature: String(systemIssueReport.value?.acceptedBySignature ?? ''),
        evaluatedBy: String(systemIssueReport.value?.evaluatedBy ?? ''),
        evaluatedByDate: String(systemIssueReport.value?.evaluatedByDate ?? ''),
        evaluatedBySignature: String(systemIssueReport.value?.evaluatedBySignature ?? ''),
        approvedBy: String(systemIssueReport.value?.approvedBy ?? ''),
        approvedByDate: String(systemIssueReport.value?.approvedByDate ?? ''),
        approvedBySignature: String(systemIssueReport.value?.approvedBySignature ?? ''),
    },
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
    <Head title="IT Governance Request" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="-mx-6 -mt-20 min-h-screen bg-[#0b2e59] text-white">
            <form
                class="mx-auto flex w-full max-w-5xl flex-col gap-0 px-6 pb-12 pt-6"
                @submit.prevent="submitForm"
            >
                <h1 class="mb-4 text-lg font-semibold tracking-tight text-white">
                    IT Governance Request
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
                            <select
                                v-if="isEditable"
                                v-model="form.natureOfRequestId"
                                class="h-8 w-full appearance-none rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20"
                            >
                                <option value="" disabled>Select nature of request</option>
                                <option
                                    v-for="opt in natureList"
                                    :key="opt.id"
                                    :value="String(opt.id)"
                                >
                                    {{ opt.name }}
                                </option>
                            </select>
                            <input
                                v-else
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

                <div
                    v-if="isSystemDevelopment && systemDevelopmentSurvey"
                    class="mt-4 rounded-lg border border-white/15 bg-white/5 px-4 py-3 shadow-sm"
                >
                    <h2 class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-white/70">
                        Systems Development Survey Form
                    </h2>

                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Title of Proposed System
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="String(systemDevelopmentSurvey.titleOfProposedSystem ?? '')"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Target Completion
                            </label>
                            <input
                                v-model="form.systemDevelopmentSurvey.targetCompletion"
                                type="date"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Assigned Systems Engineer
                            </label>
                            <select
                                v-model="form.systemDevelopmentSurvey.assignedSystemsEngineer"
                                class="h-8 w-full appearance-none rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            >
                                <option value="" disabled>Select engineer</option>
                                <option v-for="staff in props.staffOptions" :key="staff.id" :value="staff.name">
                                    {{ staff.name }}
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Office End-User
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="String(systemDevelopmentSurvey.officeEndUser ?? '')"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3">
                        <div class="rounded border border-white/15 bg-white/5 px-3 py-2">
                            <p class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                I. Services or Features
                            </p>
                            <div class="mt-2 grid gap-2">
                                <div
                                    v-for="(row, index) in (systemDevelopmentSurvey.servicesOrFeatures ?? [])"
                                    :key="`sof-view-${index}`"
                                    class="grid gap-1 rounded bg-white/5 px-2 py-1.5 text-[11px] text-white/90 sm:grid-cols-3"
                                >
                                    <div class="text-white/80">
                                        <span class="text-white/60">Service/Feature:</span>
                                        {{ row.serviceFeature ?? '' }}
                                    </div>
                                    <div class="text-white/80 sm:col-span-1">
                                        <span class="text-white/60">Accessibility:</span>
                                        {{ row.accessibility ?? '' }}
                                    </div>
                                    <div class="text-white/80 sm:col-span-3">
                                        <span class="text-white/60">Specifics:</span>
                                        {{ row.specifics ?? '' }}
                                    </div>
                                </div>
                                <p
                                    v-if="!(systemDevelopmentSurvey.servicesOrFeatures ?? []).length"
                                    class="text-[10px] text-white/60"
                                >
                                    No entries provided.
                                </p>
                            </div>
                        </div>

                        <div class="rounded border border-white/15 bg-white/5 px-3 py-2">
                            <p class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                II. Data Gathering
                            </p>
                            <div class="mt-2 grid gap-2">
                                <div
                                    v-for="(row, index) in (systemDevelopmentSurvey.dataGathering ?? [])"
                                    :key="`dg-view-${index}`"
                                    class="grid gap-1 rounded bg-white/5 px-2 py-1.5 text-[11px] text-white/90 sm:grid-cols-3"
                                >
                                    <div class="text-white/80">
                                        <span class="text-white/60">Data required:</span>
                                        {{ row.dataRequired ?? '' }}
                                    </div>
                                    <div class="text-white/80 sm:col-span-3">
                                        <span class="text-white/60">Specifics:</span>
                                        {{ row.specifics ?? '' }}
                                    </div>
                                </div>
                                <p
                                    v-if="!(systemDevelopmentSurvey.dataGathering ?? []).length"
                                    class="text-[10px] text-white/60"
                                >
                                    No entries provided.
                                </p>
                            </div>
                        </div>

                        <div class="rounded border border-white/15 bg-white/5 px-3 py-2">
                            <p class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                III. Forms
                            </p>
                            <div class="mt-2 grid gap-2">
                                <div
                                    v-for="(row, index) in (systemDevelopmentSurvey.forms ?? [])"
                                    :key="`forms-view-${index}`"
                                    class="grid gap-1 rounded bg-white/5 px-2 py-1.5 text-[11px] text-white/90 sm:grid-cols-3"
                                >
                                    <div class="text-white/80 sm:col-span-3">
                                        <span class="text-white/60">Title of form:</span>
                                        {{ row.titleOfForm ?? '' }}
                                    </div>
                                    <div class="text-white/80 sm:col-span-3">
                                        <span class="text-white/60">Description:</span>
                                        {{ row.description ?? '' }}
                                    </div>
                                </div>
                                <p
                                    v-if="!(systemDevelopmentSurvey.forms ?? []).length"
                                    class="text-[10px] text-white/60"
                                >
                                    No entries provided.
                                </p>
                            </div>
                        </div>

                        <div class="rounded border border-white/15 bg-white/5 px-3 py-2">
                            <p class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                IV. Flow (SOP)
                            </p>
                            <p class="mt-2 text-[11px] text-white/90 whitespace-pre-wrap">
                                {{ String(systemDevelopmentSurvey.flowSop ?? '') }}
                            </p>
                        </div>

                        <div class="rounded border border-white/15 bg-white/5 px-3 py-2">
                            <p class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Head of Office
                            </p>
                            <p class="mt-2 text-[11px] text-white/90">
                                {{ String(systemDevelopmentSurvey.headOfOffice ?? '') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="isSystemModification && systemChangeRequestForm"
                    class="mt-4 rounded-lg border border-white/15 bg-white/5 px-4 py-3 shadow-sm"
                >
                    <h2 class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-white/70">
                        System Change Request Form
                    </h2>

                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Control Number
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="String(systemChangeRequestForm.controlNumber ?? '')"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Date
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="String(systemChangeRequestForm.date ?? '')"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Office/Division
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="String(systemChangeRequestForm.officeDivision ?? '')"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Requested by
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="String(systemChangeRequestForm.requestedByName ?? '')"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5 sm:col-span-2">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Name of Software
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="String(systemChangeRequestForm.nameOfSoftware ?? '')"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5 sm:col-span-2">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Type of Request
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="String(systemChangeRequestForm.typeOfRequest ?? '')"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3">
                        <div class="rounded border border-white/15 bg-white/5 px-3 py-2">
                            <p class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Description of Request
                            </p>
                            <p class="mt-2 whitespace-pre-wrap text-[11px] text-white/90">
                                {{ String(systemChangeRequestForm.descriptionOfRequest ?? '') }}
                            </p>
                        </div>

                        <div class="rounded border border-white/15 bg-white/5 px-3 py-2">
                            <p class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Purpose/Objective of Modification
                            </p>
                            <p class="mt-2 whitespace-pre-wrap text-[11px] text-white/90">
                                {{ String(systemChangeRequestForm.purposeObjectiveOfModification ?? '') }}
                            </p>
                        </div>

                        <div class="rounded border border-white/15 bg-white/5 px-3 py-2">
                            <p class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Detailed Description of Requested Change
                            </p>
                            <p class="mt-2 whitespace-pre-wrap text-[11px] text-white/90">
                                {{ String(systemChangeRequestForm.detailedDescriptionOfRequestedChange ?? '') }}
                            </p>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="grid gap-0.5">
                                <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                    Evaluated by
                                </label>
                                <input
                                    v-model="form.systemChangeRequestForm.evaluatedBy"
                                    type="text"
                                    class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                    :disabled="!isEditable"
                                />
                            </div>
                            <div class="grid gap-0.5">
                                <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                    Approved by
                                </label>
                                <input
                                    v-model="form.systemChangeRequestForm.approvedBy"
                                    type="text"
                                    class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                    :disabled="!isEditable"
                                />
                            </div>
                            <div class="grid gap-0.5">
                                <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                    Noted by
                                </label>
                                <input
                                    v-model="form.systemChangeRequestForm.notedBy"
                                    type="text"
                                    class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                    :disabled="!isEditable"
                                />
                            </div>
                            <div class="grid gap-0.5 lg:col-span-4">
                                <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                    Remarks
                                </label>
                                <input
                                    v-model="form.systemChangeRequestForm.remarks"
                                    type="text"
                                    class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                    :disabled="!isEditable"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="isSystemErrorBugReport && systemIssueReport"
                    class="mt-4 rounded-lg border border-white/15 bg-white/5 px-4 py-3 shadow-sm"
                >
                    <h2 class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-white/70">
                        System Issue Report
                    </h2>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Control Number
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="String(systemIssueReport.controlNumber ?? '')"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Requesting Department
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="String(systemIssueReport.requestingDepartment ?? '')"
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
                                :value="String(systemIssueReport.dateFiled ?? '')"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Requesting Employee
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="String(systemIssueReport.requestingEmployee ?? '')"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Name of Software
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="String(systemIssueReport.nameOfSoftware ?? '')"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                        <div class="grid gap-0.5 sm:col-span-2">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Type of Request
                            </label>
                            <input
                                type="text"
                                readonly
                                :value="Array.isArray(systemIssueReport.typeOfRequest) ? systemIssueReport.typeOfRequest.join(', ') : String(systemIssueReport.typeOfRequest ?? '')"
                                class="h-8 w-full cursor-not-allowed rounded border border-white/20 bg-slate-100/90 px-2 text-[11px] text-slate-600"
                            />
                        </div>
                    </div>
                    <div class="mt-3 rounded border border-white/15 bg-white/5 px-3 py-2">
                        <p class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                            Error Summary/Title
                        </p>
                        <p class="mt-2 text-[11px] text-white/90">
                            {{ String(systemIssueReport.errorSummaryTitle ?? '') }}
                        </p>
                    </div>
                    <div class="mt-3 rounded border border-white/15 bg-white/5 px-3 py-2">
                        <p class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                            Detailed Description
                        </p>
                        <p class="mt-2 whitespace-pre-wrap text-[11px] text-white/90">
                            {{ String(systemIssueReport.detailedDescription ?? '') }}
                        </p>
                    </div>
                    <div v-if="systemIssueReportAttachments.length" class="mt-3 flex flex-wrap gap-2">
                        <a
                            v-for="att in systemIssueReportAttachments"
                            :key="att.name"
                            :href="att.url ?? undefined"
                            :target="att.url ? '_blank' : undefined"
                            :rel="att.url ? 'noreferrer' : undefined"
                            class="inline-flex rounded bg-white/90 px-2 py-1 text-[10px] font-medium text-slate-600 hover:bg-white"
                        >
                            {{ att.name }}
                        </a>
                    </div>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Reported By
                            </label>
                            <input
                                v-model="form.systemIssueReport.reportedBy"
                                type="text"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Reported By Date
                            </label>
                            <input
                                v-model="form.systemIssueReport.reportedByDate"
                                type="date"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Reported By Signature
                            </label>
                            <input
                                v-model="form.systemIssueReport.reportedBySignature"
                                type="text"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Accepted By
                            </label>
                            <input
                                v-model="form.systemIssueReport.acceptedBy"
                                type="text"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Accepted By Date
                            </label>
                            <input
                                v-model="form.systemIssueReport.acceptedByDate"
                                type="date"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Accepted By Signature
                            </label>
                            <input
                                v-model="form.systemIssueReport.acceptedBySignature"
                                type="text"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Evaluated By
                            </label>
                            <input
                                v-model="form.systemIssueReport.evaluatedBy"
                                type="text"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Evaluated By Date
                            </label>
                            <input
                                v-model="form.systemIssueReport.evaluatedByDate"
                                type="date"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Evaluated By Signature
                            </label>
                            <input
                                v-model="form.systemIssueReport.evaluatedBySignature"
                                type="text"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Approved By
                            </label>
                            <input
                                v-model="form.systemIssueReport.approvedBy"
                                type="text"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Approved By Date
                            </label>
                            <input
                                v-model="form.systemIssueReport.approvedByDate"
                                type="date"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
                            />
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Approved By Signature
                            </label>
                            <input
                                v-model="form.systemIssueReport.approvedBySignature"
                                type="text"
                                class="h-8 w-full rounded border border-white/30 bg-white px-2 text-[11px] text-slate-900 placeholder:text-slate-400 focus:border-white/60 focus:outline-none focus:ring-2 focus:ring-white/20 disabled:bg-white/70 disabled:text-slate-500"
                                :disabled="!isEditable"
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
                            <div
                                class="flex h-8 w-full items-center rounded border border-white/30 bg-white pr-2 focus-within:border-white/60 focus-within:ring-2 focus-within:ring-white/20 disabled:bg-white/70"
                                :class="{ 'cursor-not-allowed opacity-70': !isEditable }"
                            >
                                <input
                                    v-model="form.dateReceived"
                                    type="date"
                                    class="min-w-0 flex-1 cursor-pointer border-0 bg-transparent px-2 py-0 text-[11px] text-slate-900 scheme-light focus:outline-none disabled:cursor-not-allowed disabled:text-slate-500"
                                    :disabled="!isEditable"
                                    @keydown.prevent
                                />
                                <Icon
                                    name="calendar"
                                    class="h-4 w-4 shrink-0 text-slate-500 pointer-events-none"
                                />
                            </div>
                        </div>
                        <div class="grid gap-0.5">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Date Started
                            </label>
                            <div
                                class="flex h-8 w-full items-center rounded border border-white/30 bg-white pr-2 focus-within:border-white/60 focus-within:ring-2 focus-within:ring-white/20 disabled:bg-white/70"
                                :class="{ 'cursor-not-allowed opacity-70': !isEditable }"
                            >
                                <input
                                    v-model="form.dateStarted"
                                    type="date"
                                    class="min-w-0 flex-1 cursor-pointer border-0 bg-transparent px-2 py-0 text-[11px] text-slate-900 scheme-light focus:outline-none disabled:cursor-not-allowed disabled:text-slate-500"
                                    :disabled="!isEditable"
                                    @keydown.prevent
                                />
                                <Icon
                                    name="calendar"
                                    class="h-4 w-4 shrink-0 text-slate-500 pointer-events-none"
                                />
                            </div>
                            <p v-if="fieldError('dateStarted')" class="mt-0.5 text-[10px] text-red-300">
                                {{ fieldError('dateStarted') }}
                            </p>
                        </div>
                        <div class="grid gap-0.5 sm:col-span-2 lg:col-span-1">
                            <label class="text-[9px] font-semibold uppercase tracking-widest text-white/60">
                                Estimated Completion Date
                            </label>
                            <div
                                class="flex h-8 w-full items-center rounded border border-white/30 bg-white pr-2 focus-within:border-white/60 focus-within:ring-2 focus-within:ring-white/20 disabled:bg-white/70"
                                :class="{ 'cursor-not-allowed opacity-70': !isEditable }"
                            >
                                <input
                                    v-model="form.estimatedCompletionDate"
                                    type="date"
                                    class="min-w-0 flex-1 cursor-pointer border-0 bg-transparent px-2 py-0 text-[11px] text-slate-900 scheme-light focus:outline-none disabled:cursor-not-allowed disabled:text-slate-500"
                                    :disabled="!isEditable"
                                    @keydown.prevent
                                />
                                <Icon
                                    name="calendar"
                                    class="h-4 w-4 shrink-0 text-slate-500 pointer-events-none"
                                />
                            </div>
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
