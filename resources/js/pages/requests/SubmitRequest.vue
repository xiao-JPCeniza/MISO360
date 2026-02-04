<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard, submitRequest } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type NatureOption = {
    id: number;
    name: string;
};

type OfficeOption = {
    id: number;
    name: string;
};

type OfficeUser = {
    id: number;
    name: string;
    office_designation_id: number;
};

type SystemsEngineerOption = {
    id: number;
    name: string;
};

type AttachmentEntry = {
    file: File;
    key: string;
};

type ServiceOrFeatureEntry = {
    serviceFeature: string;
    specifics: string;
    accessibility: '' | 'Public View' | 'Admin/User View Only';
};

type DataGatheringEntry = {
    dataRequired: string;
    specifics: string;
};

type FormReferenceEntry = {
    titleOfForm: string;
    description: string;
};

type SystemDevelopmentSurvey = {
    titleOfProposedSystem: string;
    targetCompletion: string;
    assignedSystemsEngineer: string;
    officeEndUser: string;
    servicesOrFeatures: ServiceOrFeatureEntry[];
    dataGathering: DataGatheringEntry[];
    forms: FormReferenceEntry[];
    flowSop: string;
    headOfOffice: string;
};

const props = withDefaults(
    defineProps<{
        controlTicketNumber: string;
        natureOfRequests: NatureOption[];
        preSelectedNatureId?: number | null;
        isAdmin: boolean;
        officeOptions: OfficeOption[];
        officeUsers: OfficeUser[];
        systemsEngineerOptions: SystemsEngineerOption[];
        maxAttachments: number;
        maxAttachmentSizeMb: number;
        qrCodePattern: string;
    }>(),
    { preSelectedNatureId: null },
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Submit a Ticket Request',
        href: '/submit-request',
    },
];

const validPreSelectedId =
    props.preSelectedNatureId != null &&
    props.natureOfRequests.some((n) => n.id === props.preSelectedNatureId)
        ? String(props.preSelectedNatureId)
        : '';

const form = useForm({
    controlTicketNumber: props.controlTicketNumber,
    natureOfRequestId: validPreSelectedId,
    officeDesignationId: '',
    requestedForUserId: '',
    description: '',
    hasQrCode: false,
    qrCodeNumber: '',
    attachments: [] as File[],
    systemDevelopmentSurveyFormAttachments: {} as Record<string, File>,
    systemDevelopmentSurvey: {
        titleOfProposedSystem: '',
        targetCompletion: '',
        assignedSystemsEngineer: '',
        officeEndUser: '',
        servicesOrFeatures: [{ serviceFeature: '', specifics: '', accessibility: '' }],
        dataGathering: [{ dataRequired: '', specifics: '' }],
        forms: [{ titleOfForm: '', description: '' }],
        flowSop: '',
        headOfOffice: '',
    } as SystemDevelopmentSurvey,
});

const attachmentEntries = ref<AttachmentEntry[]>([]);
const fileInputRef = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const uploadError = ref('');
const descriptionTouched = ref(false);
const submitAttempted = ref(false);

const maxAttachments = computed(() => props.maxAttachments);
const maxSizeBytes = computed(() => props.maxAttachmentSizeMb * 1024 * 1024);
const descriptionLength = computed(() => form.description.length);

const descriptionError = computed(() => {
    if (!descriptionTouched.value && !submitAttempted.value) {
        return '';
    }
    if (!form.description.trim()) {
        return 'Description is required.';
    }
    if (descriptionLength.value < 10) {
        return 'Description must be at least 10 characters.';
    }
    if (descriptionLength.value > 1000) {
        return 'Description may not exceed 1000 characters.';
    }
    return '';
});

const serverDescriptionError = computed(() => form.errors.description ?? '');
const natureError = computed(() => {
    if (!submitAttempted.value) {
        return '';
    }
    return form.natureOfRequestId ? '' : 'Please select a nature of request.';
});
const officeError = computed(() => {
    if (!props.isAdmin || !submitAttempted.value) {
        return '';
    }
    return form.officeDesignationId ? '' : 'Please select an office designation.';
});
const requestedUserError = computed(() => {
    if (!props.isAdmin || !submitAttempted.value) {
        return '';
    }
    return form.requestedForUserId ? '' : 'Please select a user for this request.';
});

const selectedNatureName = computed(() => {
    const selected = props.natureOfRequests.find(
        (option) => String(option.id) === String(form.natureOfRequestId),
    );
    return selected?.name ?? '';
});

const isSystemDevelopment = computed(() => {
    return selectedNatureName.value.trim().toLowerCase() === 'system development';
});

const surveyErrors = computed(() => {
    if (!submitAttempted.value || !isSystemDevelopment.value) {
        return {};
    }

    const s = form.systemDevelopmentSurvey as SystemDevelopmentSurvey;
    const errors: Record<string, string> = {};

    if (!s.titleOfProposedSystem.trim()) {
        errors['systemDevelopmentSurvey.titleOfProposedSystem'] =
            'Title of Proposed System is required.';
    }
    if (!s.flowSop.trim()) {
        errors['systemDevelopmentSurvey.flowSop'] = 'Flow (SOP) is required.';
    }
    if (!s.headOfOffice.trim()) {
        errors['systemDevelopmentSurvey.headOfOffice'] = 'Head of Office is required.';
    }

    if (!s.servicesOrFeatures.length) {
        errors['systemDevelopmentSurvey.servicesOrFeatures'] =
            'At least one service/feature entry is required.';
    } else {
        s.servicesOrFeatures.forEach((row, index) => {
            if (!row.serviceFeature.trim()) {
                errors[`systemDevelopmentSurvey.servicesOrFeatures.${index}.serviceFeature`] =
                    'Service/Feature is required.';
            }
            if (!row.specifics.trim()) {
                errors[`systemDevelopmentSurvey.servicesOrFeatures.${index}.specifics`] =
                    'Specifics is required.';
            }
            if (!row.accessibility) {
                errors[`systemDevelopmentSurvey.servicesOrFeatures.${index}.accessibility`] =
                    'Accessibility is required.';
            }
        });
    }

    if (!s.dataGathering.length) {
        errors['systemDevelopmentSurvey.dataGathering'] =
            'At least one data gathering entry is required.';
    } else {
        s.dataGathering.forEach((row, index) => {
            if (!row.dataRequired.trim()) {
                errors[`systemDevelopmentSurvey.dataGathering.${index}.dataRequired`] =
                    'Data required is required.';
            }
            if (!row.specifics.trim()) {
                errors[`systemDevelopmentSurvey.dataGathering.${index}.specifics`] =
                    'Specifics is required.';
            }
        });
    }

    return errors;
});

const filteredOfficeUsers = computed(() => {
    if (!form.officeDesignationId) {
        return [];
    }
    return props.officeUsers.filter(
        (user) => String(user.office_designation_id) === String(form.officeDesignationId),
    );
});

watch(
    () => form.hasQrCode,
    (value) => {
        if (!value) {
            form.qrCodeNumber = '';
            form.clearErrors('qrCodeNumber');
        }
    },
);

watch(
    () => form.officeDesignationId,
    () => {
        form.requestedForUserId = '';
        form.clearErrors('requestedForUserId');
    },
);

watch(
    () => [form.officeDesignationId, form.requestedForUserId],
    () => {
        const officeName = props.officeOptions.find(
            (option) => String(option.id) === String(form.officeDesignationId),
        )?.name;

        if (props.isAdmin) {
            form.systemDevelopmentSurvey.officeEndUser = officeName ?? '';
        }
    },
);

watch(
    () => form.natureOfRequestId,
    () => {
        form.clearErrors('systemDevelopmentSurvey');
    },
);

watch(
    () => isSystemDevelopment.value,
    (enabled) => {
        if (!enabled) {
            return;
        }

        if (!props.isAdmin) {
            form.systemDevelopmentSurvey.assignedSystemsEngineer = '';
            form.systemDevelopmentSurvey.targetCompletion = '';
        }
    },
);

const acceptedTypes = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'video/mp4',
    'video/quicktime',
];
const acceptedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov'];
const acceptAttribute = acceptedExtensions.map((ext) => `.${ext}`).join(',');

function formatSize(size: number) {
    if (size < 1024) {
        return `${size} B`;
    }
    if (size < 1024 * 1024) {
        return `${(size / 1024).toFixed(1)} KB`;
    }
    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
}

function handleFiles(files: File[]) {
    uploadError.value = '';

    if (!files.length) {
        return;
    }

    const remainingSlots = maxAttachments.value - attachmentEntries.value.length;

    if (remainingSlots <= 0) {
        uploadError.value = `You can upload up to ${maxAttachments.value} files.`;
        return;
    }

    const limitedFiles = files.slice(0, remainingSlots);
    const rejected: string[] = [];
    const accepted: AttachmentEntry[] = [];

    limitedFiles.forEach((file, index) => {
        const extension = file.name.split('.').pop()?.toLowerCase() ?? '';
        const isValidType =
            acceptedTypes.includes(file.type) || acceptedExtensions.includes(extension);

        if (!isValidType) {
            rejected.push(`${file.name} (unsupported format)`);
            return;
        }

        if (file.size > maxSizeBytes.value) {
            rejected.push(`${file.name} (exceeds ${props.maxAttachmentSizeMb} MB)`);
            return;
        }

        accepted.push({
            file,
            key: `${file.name}-${file.size}-${index}-${Date.now()}`,
        });
    });

    if (rejected.length) {
        uploadError.value = rejected.join(', ');
    }

    if (accepted.length) {
        attachmentEntries.value = [...attachmentEntries.value, ...accepted];
        form.attachments = attachmentEntries.value.map((entry) => entry.file);
    }
}

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const files = target.files ? Array.from(target.files) : [];
    handleFiles(files);

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function removeAttachment(index: number) {
    attachmentEntries.value.splice(index, 1);
    form.attachments = attachmentEntries.value.map((entry) => entry.file);
}

function onDrop(event: DragEvent) {
    event.preventDefault();
    isDragging.value = false;

    const files = event.dataTransfer ? Array.from(event.dataTransfer.files) : [];
    handleFiles(files);
}

function onDragOver(event: DragEvent) {
    event.preventDefault();
    isDragging.value = true;
}

function onDragLeave() {
    isDragging.value = false;
}

function submitTicket() {
    submitAttempted.value = true;
    descriptionTouched.value = true;
    uploadError.value = '';

    if (
        natureError.value ||
        descriptionError.value ||
        officeError.value ||
        requestedUserError.value ||
        Object.keys(surveyErrors.value).length > 0
    ) {
        if (natureError.value) {
            form.setError('natureOfRequestId', natureError.value);
        }
        if (descriptionError.value) {
            form.setError('description', descriptionError.value);
        }
        if (officeError.value) {
            form.setError('officeDesignationId', officeError.value);
        }
        if (requestedUserError.value) {
            form.setError('requestedForUserId', requestedUserError.value);
        }

        Object.entries(surveyErrors.value).forEach(([key, message]) => {
            form.setError(key as never, message);
        });
        return;
    }

    form.post(submitRequest().url, {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Submit a Ticket Request" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="-mx-6 -mt-20 min-h-screen bg-background text-foreground">
            <div class="mx-auto flex w-full max-w-4xl flex-col gap-5 px-6 pb-12 pt-12">
                <div class="space-y-2">
                    <h1 class="text-3xl font-bold text-foreground md:text-4xl">
                        Submit a Ticket Request
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Complete the form below and submit your request.
                    </p>
                </div>

                <form
                    class="rounded-2xl border border-border bg-card p-5 text-card-foreground shadow-lg md:p-6 dark:border-white/10 dark:shadow-black/20"
                    @submit.prevent="submitTicket"
                >
                    <div class="grid gap-4">
                        <div class="grid gap-3">
                            <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                Control Ticket Number
                            </label>
                            <input
                                v-model="form.controlTicketNumber"
                                type="text"
                                readonly
                                class="h-9 w-full rounded-md border border-input bg-input px-3 text-sm font-semibold text-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                            />
                        </div>

                        <div
                            v-if="isAdmin"
                            class="grid gap-4 rounded-xl border border-border bg-muted/30 p-4 md:grid-cols-2 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="grid gap-3">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                    Office Designation
                                </label>
                                <select
                                    v-model="form.officeDesignationId"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                    required
                                >
                                    <option disabled value="">Select an office</option>
                                    <option
                                        v-for="option in officeOptions"
                                        :key="option.id"
                                        :value="String(option.id)"
                                    >
                                        {{ option.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.officeDesignationId || officeError" class="text-xs text-destructive">
                                    {{ form.errors.officeDesignationId || officeError }}
                                </p>
                            </div>

                            <div class="grid gap-3">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                    Requesting For
                                </label>
                                <select
                                    v-model="form.requestedForUserId"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground disabled:opacity-70 focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                    :disabled="!form.officeDesignationId"
                                    required
                                >
                                    <option disabled value="">Select a user</option>
                                    <option
                                        v-for="user in filteredOfficeUsers"
                                        :key="user.id"
                                        :value="String(user.id)"
                                    >
                                        {{ user.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.requestedForUserId || requestedUserError" class="text-xs text-destructive">
                                    {{ form.errors.requestedForUserId || requestedUserError }}
                                </p>
                                <p v-if="form.officeDesignationId && !filteredOfficeUsers.length" class="text-xs text-muted-foreground">
                                    No active users found for this office.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-3">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                    Nature of Request
                                </label>
                                <select
                                    v-model="form.natureOfRequestId"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                    required
                                >
                                    <option disabled value="">Select a request category</option>
                                    <option
                                        v-for="option in natureOfRequests"
                                        :key="option.id"
                                        :value="String(option.id)"
                                    >
                                        {{ option.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.natureOfRequestId || natureError" class="text-xs text-destructive">
                                    {{ form.errors.natureOfRequestId || natureError }}
                                </p>
                            </div>

                            <div class="grid gap-3 rounded-xl border border-border bg-muted/30 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                            QR Code for Unit
                                        </p>
                                        <p class="text-xs text-muted-foreground">Optional asset tag</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-ring rounded">
                                        <input
                                            v-model="form.hasQrCode"
                                            type="checkbox"
                                            class="peer sr-only"
                                        />
                                        <div class="h-5 w-9 rounded-full bg-muted after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-background after:border after:border-input after:transition-all peer-checked:bg-primary peer-checked:after:translate-x-4 peer-checked:after:border-0 dark:bg-white/20 dark:after:bg-white"></div>
                                    </label>
                                </div>

                                <div v-if="form.hasQrCode" class="grid gap-2">
                                    <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                        QR Code Number
                                    </label>
                                    <input
                                        v-model="form.qrCodeNumber"
                                        type="text"
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                        placeholder="MIS-UID-00001"
                                        :pattern="qrCodePattern"
                                    />
                                    <p v-if="form.errors.qrCodeNumber" class="text-xs text-destructive">
                                        {{ form.errors.qrCodeNumber }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="isSystemDevelopment"
                            class="grid gap-5 rounded-2xl border border-border bg-muted/20 p-5 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                    Systems Development Survey Form (Required)
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    This form must be completed before the ticket can be submitted.
                                </p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                        Title of Proposed System
                                    </label>
                                    <input
                                        v-model="form.systemDevelopmentSurvey.titleOfProposedSystem"
                                        type="text"
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                    />
                                    <p v-if="form.errors['systemDevelopmentSurvey.titleOfProposedSystem']" class="text-xs text-destructive">
                                        {{ form.errors['systemDevelopmentSurvey.titleOfProposedSystem'] }}
                                    </p>
                                </div>

                                <div class="grid gap-2">
                                    <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                        Target Completion (Admin Only)
                                    </label>
                                    <input
                                        v-model="form.systemDevelopmentSurvey.targetCompletion"
                                        type="date"
                                        :readonly="!isAdmin"
                                        :disabled="!isAdmin"
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground disabled:opacity-70 focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                    />
                                    <p v-if="!isAdmin" class="text-xs text-muted-foreground">
                                        Assigned by admin/super admin during evaluation.
                                    </p>
                                </div>

                                <div class="grid gap-2">
                                    <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                        Assigned Systems Engineer (Admin Only)
                                    </label>
                                    <div class="grid gap-2">
                                        <select
                                            v-model="form.systemDevelopmentSurvey.assignedSystemsEngineer"
                                            :disabled="!isAdmin"
                                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground disabled:opacity-70 focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                        >
                                            <option disabled value="">Select engineer</option>
                                            <option
                                                v-for="engineer in systemsEngineerOptions"
                                                :key="engineer.id"
                                                :value="engineer.name"
                                            >
                                                {{ engineer.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <p v-if="!isAdmin" class="text-xs text-muted-foreground">
                                        Assigned by admin/super admin during evaluation.
                                    </p>
                                </div>

                                <div class="grid gap-2">
                                    <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                        Office End-User (Auto)
                                    </label>
                                    <input
                                        v-model="form.systemDevelopmentSurvey.officeEndUser"
                                        type="text"
                                        readonly
                                        class="h-9 w-full cursor-not-allowed rounded-md border border-input bg-muted/40 px-3 text-sm text-foreground placeholder:text-muted-foreground focus-visible:outline-none"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Automatically set to the requester’s office.
                                    </p>
                                </div>
                            </div>

                            <div class="grid gap-3">
                                <div class="flex items-center justify-between gap-3">
                                    <h2 class="text-sm font-semibold text-foreground">I. Services or Features</h2>
                                    <button
                                        type="button"
                                        class="rounded-md border border-border bg-background px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-foreground hover:bg-muted/40 dark:border-white/10"
                                        @click="form.systemDevelopmentSurvey.servicesOrFeatures.push({ serviceFeature: '', specifics: '', accessibility: '' })"
                                    >
                                        Add row
                                    </button>
                                </div>
                                <div class="grid gap-3">
                                    <div
                                        v-for="(row, index) in form.systemDevelopmentSurvey.servicesOrFeatures"
                                        :key="`sof-${index}`"
                                        class="grid gap-3 rounded-xl border border-border bg-card p-4 dark:border-white/10"
                                    >
                                        <div class="grid gap-3 md:grid-cols-3">
                                            <div class="grid gap-2">
                                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                                    Service/Feature
                                                </label>
                                                <input
                                                    v-model="row.serviceFeature"
                                                    type="text"
                                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                                />
                                                <p
                                                    v-if="form.errors[`systemDevelopmentSurvey.servicesOrFeatures.${index}.serviceFeature`]"
                                                    class="text-xs text-destructive"
                                                >
                                                    {{ form.errors[`systemDevelopmentSurvey.servicesOrFeatures.${index}.serviceFeature`] }}
                                                </p>
                                            </div>
                                            <div class="grid gap-2 md:col-span-2">
                                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                                    Specifics
                                                </label>
                                                <input
                                                    v-model="row.specifics"
                                                    type="text"
                                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                                />
                                                <p
                                                    v-if="form.errors[`systemDevelopmentSurvey.servicesOrFeatures.${index}.specifics`]"
                                                    class="text-xs text-destructive"
                                                >
                                                    {{ form.errors[`systemDevelopmentSurvey.servicesOrFeatures.${index}.specifics`] }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="grid gap-2 md:grid-cols-3">
                                            <div class="grid gap-2">
                                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                                    Accessibility
                                                </label>
                                                <select
                                                    v-model="row.accessibility"
                                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                                >
                                                    <option disabled value="">Select</option>
                                                    <option value="Public View">Public View</option>
                                                    <option value="Admin/User View Only">Admin/User View Only</option>
                                                </select>
                                                <p
                                                    v-if="form.errors[`systemDevelopmentSurvey.servicesOrFeatures.${index}.accessibility`]"
                                                    class="text-xs text-destructive"
                                                >
                                                    {{ form.errors[`systemDevelopmentSurvey.servicesOrFeatures.${index}.accessibility`] }}
                                                </p>
                                            </div>
                                            <div class="flex items-end justify-end md:col-span-2">
                                                <button
                                                    type="button"
                                                    class="text-xs font-semibold uppercase tracking-[0.2em] text-destructive hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                                    :disabled="form.systemDevelopmentSurvey.servicesOrFeatures.length <= 1"
                                                    @click="form.systemDevelopmentSurvey.servicesOrFeatures.splice(index, 1)"
                                                >
                                                    Remove row
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="form.errors['systemDevelopmentSurvey.servicesOrFeatures']" class="text-xs text-destructive">
                                    {{ form.errors['systemDevelopmentSurvey.servicesOrFeatures'] }}
                                </p>
                            </div>

                            <div class="grid gap-3">
                                <div class="flex items-center justify-between gap-3">
                                    <h2 class="text-sm font-semibold text-foreground">II. Data Gathering</h2>
                                    <button
                                        type="button"
                                        class="rounded-md border border-border bg-background px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-foreground hover:bg-muted/40 dark:border-white/10"
                                        @click="form.systemDevelopmentSurvey.dataGathering.push({ dataRequired: '', specifics: '' })"
                                    >
                                        Add row
                                    </button>
                                </div>
                                <div class="grid gap-3">
                                    <div
                                        v-for="(row, index) in form.systemDevelopmentSurvey.dataGathering"
                                        :key="`dg-${index}`"
                                        class="grid gap-3 rounded-xl border border-border bg-card p-4 dark:border-white/10"
                                    >
                                        <div class="grid gap-3 md:grid-cols-3">
                                            <div class="grid gap-2">
                                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                                    Data Required
                                                </label>
                                                <input
                                                    v-model="row.dataRequired"
                                                    type="text"
                                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                                />
                                                <p
                                                    v-if="form.errors[`systemDevelopmentSurvey.dataGathering.${index}.dataRequired`]"
                                                    class="text-xs text-destructive"
                                                >
                                                    {{ form.errors[`systemDevelopmentSurvey.dataGathering.${index}.dataRequired`] }}
                                                </p>
                                            </div>
                                            <div class="grid gap-2 md:col-span-2">
                                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                                    Specifics
                                                </label>
                                                <input
                                                    v-model="row.specifics"
                                                    type="text"
                                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                                />
                                                <p
                                                    v-if="form.errors[`systemDevelopmentSurvey.dataGathering.${index}.specifics`]"
                                                    class="text-xs text-destructive"
                                                >
                                                    {{ form.errors[`systemDevelopmentSurvey.dataGathering.${index}.specifics`] }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-end justify-end">
                                            <button
                                                type="button"
                                                class="text-xs font-semibold uppercase tracking-[0.2em] text-destructive hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                                :disabled="form.systemDevelopmentSurvey.dataGathering.length <= 1"
                                                @click="form.systemDevelopmentSurvey.dataGathering.splice(index, 1)"
                                            >
                                                Remove row
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="form.errors['systemDevelopmentSurvey.dataGathering']" class="text-xs text-destructive">
                                    {{ form.errors['systemDevelopmentSurvey.dataGathering'] }}
                                </p>
                            </div>

                            <div class="grid gap-3">
                                <div class="flex items-center justify-between gap-3">
                                    <h2 class="text-sm font-semibold text-foreground">III. Forms</h2>
                                    <button
                                        type="button"
                                        class="rounded-md border border-border bg-background px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-foreground hover:bg-muted/40 dark:border-white/10"
                                        @click="form.systemDevelopmentSurvey.forms.push({ titleOfForm: '', description: '' })"
                                    >
                                        Add row
                                    </button>
                                </div>
                                <div class="grid gap-3">
                                    <div
                                        v-for="(row, index) in form.systemDevelopmentSurvey.forms"
                                        :key="`forms-${index}`"
                                        class="grid gap-3 rounded-xl border border-border bg-card p-4 dark:border-white/10"
                                    >
                                        <div class="grid gap-2">
                                            <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                                Attachment (uploaded by the user)
                                            </label>
                                            <input
                                                type="file"
                                                class="block w-full text-sm text-foreground file:mr-4 file:rounded-md file:border-0 file:bg-muted file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-[0.2em] file:text-foreground hover:file:bg-muted/70"
                                                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp"
                                                @change="(e) => {
                                                    const target = e.target as HTMLInputElement;
                                                    const file = target.files?.[0];
                                                    if (file) {
                                                        form.systemDevelopmentSurveyFormAttachments[String(index)] = file;
                                                    } else {
                                                        // eslint-disable-next-line @typescript-eslint/no-dynamic-delete
                                                        delete form.systemDevelopmentSurveyFormAttachments[String(index)];
                                                    }
                                                }"
                                            />
                                            <p v-if="form.errors[`systemDevelopmentSurveyFormAttachments.${index}`]" class="text-xs text-destructive">
                                                {{ form.errors[`systemDevelopmentSurveyFormAttachments.${index}`] }}
                                            </p>
                                        </div>
                                        <div class="grid gap-3 md:grid-cols-2">
                                            <div class="grid gap-2">
                                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                                    Title of Form
                                                </label>
                                                <input
                                                    v-model="row.titleOfForm"
                                                    type="text"
                                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                                />
                                            </div>
                                            <div class="grid gap-2 md:col-span-2">
                                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                                    Description
                                                </label>
                                                <textarea
                                                    v-model="row.description"
                                                    rows="2"
                                                    class="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                                />
                                            </div>
                                        </div>
                                        <div class="flex items-end justify-end">
                                            <button
                                                type="button"
                                                class="text-xs font-semibold uppercase tracking-[0.2em] text-destructive hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                                :disabled="form.systemDevelopmentSurvey.forms.length <= 1"
                                                @click="() => {
                                                    form.systemDevelopmentSurvey.forms.splice(index, 1);
                                                    // eslint-disable-next-line @typescript-eslint/no-dynamic-delete
                                                    delete form.systemDevelopmentSurveyFormAttachments[String(index)];
                                                }"
                                            >
                                                Remove row
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3">
                                <h2 class="text-sm font-semibold text-foreground">IV. Flow (SOP)</h2>
                                <textarea
                                    v-model="form.systemDevelopmentSurvey.flowSop"
                                    rows="4"
                                    class="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                />
                                <p v-if="form.errors['systemDevelopmentSurvey.flowSop']" class="text-xs text-destructive">
                                    {{ form.errors['systemDevelopmentSurvey.flowSop'] }}
                                </p>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                        Head of Office
                                    </label>
                                    <input
                                        v-model="form.systemDevelopmentSurvey.headOfOffice"
                                        type="text"
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                    />
                                    <p v-if="form.errors['systemDevelopmentSurvey.headOfOffice']" class="text-xs text-destructive">
                                        {{ form.errors['systemDevelopmentSurvey.headOfOffice'] }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3">
                            <div class="flex items-center justify-between">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                    Description of Request
                                </label>
                                <span class="text-xs text-muted-foreground">
                                    {{ descriptionLength }}/1000
                                </span>
                            </div>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                class="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                placeholder="Briefly describe your request..."
                                @blur="descriptionTouched = true"
                            />
                            <p v-if="descriptionError || serverDescriptionError" class="text-xs text-destructive">
                                {{ descriptionError || serverDescriptionError }}
                            </p>
                            <p v-else class="text-xs text-muted-foreground">
                                Minimum 10 characters. Maximum 1000 characters.
                            </p>
                        </div>

                        <div class="grid gap-3">
                            <div class="flex items-center justify-between">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                    Upload Photo/Videos Here (optional)
                                </label>
                                <span class="text-xs text-muted-foreground">
                                    {{ attachmentEntries.length }}/{{ maxAttachments }} files
                                </span>
                            </div>
                            <div
                                class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-border bg-muted/40 px-4 py-4 text-center transition dark:border-white/20 dark:bg-white/5"
                                :class="isDragging ? 'border-primary/50 bg-primary/10' : ''"
                                @dragover="onDragOver"
                                @dragleave="onDragLeave"
                                @drop="onDrop"
                            >
                                <div>
                                    <p class="text-sm font-semibold text-foreground">
                                        Drag and Drop Files Here
                                    </p>
                                    <p class="text-xs text-muted-foreground">or</p>
                                </div>
                                <label
                                    class="cursor-pointer rounded-full border border-primary bg-primary px-4 py-1.5 text-xs font-semibold text-primary-foreground transition-colors hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                >
                                    Browse Files
                                    <input
                                        ref="fileInputRef"
                                        type="file"
                                        class="hidden"
                                        multiple
                                        :accept="acceptAttribute"
                                        @change="onFileChange"
                                    />
                                </label>
                                <p class="text-xs text-muted-foreground">
                                    JPG, PNG, WEBP, MP4, MOV up to {{ maxAttachmentSizeMb }}MB each
                                </p>
                                <p v-if="uploadError" class="text-xs text-destructive">
                                    {{ uploadError }}
                                </p>
                                <p v-if="form.errors.attachments" class="text-xs text-destructive">
                                    {{ form.errors.attachments }}
                                </p>
                            </div>

                            <div v-if="attachmentEntries.length" class="grid gap-3">
                                <div
                                    v-for="(entry, index) in attachmentEntries"
                                    :key="entry.key"
                                    class="flex items-center justify-between rounded-lg border border-border bg-muted/30 px-3 py-2 text-sm text-foreground dark:border-white/20 dark:bg-white/5"
                                >
                                    <div>
                                            <p class="font-semibold">{{ entry.file.name }}</p>
                                            <p class="text-xs text-muted-foreground">
                                            {{ formatSize(entry.file.size) }}
                                        </p>
                                        <div
                                            v-if="form.progress"
                                                class="mt-2 h-1.5 w-48 rounded-full bg-muted"
                                        >
                                            <div
                                                    class="h-1.5 rounded-full bg-primary transition-all"
                                                :style="{
                                                    width: `${form.progress?.percentage ?? 0}%`,
                                                }"
                                            ></div>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                            class="text-xs font-semibold uppercase tracking-[0.2em] text-destructive hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                        @click="removeAttachment(index)"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-center pt-2">
                            <button
                                type="submit"
                                class="rounded-md bg-primary px-6 py-2 text-sm font-semibold text-primary-foreground shadow-lg transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Submitting...' : 'Submit Ticket' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>