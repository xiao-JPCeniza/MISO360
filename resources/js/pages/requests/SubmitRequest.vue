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

type SystemChangeRequestForm = {
    controlNumber: string;
    date: string;
    officeDivision: string;
    requestedByName: string;
    nameOfSoftware: string;
    typeOfRequest: string;
    descriptionOfRequest: string;
    purposeObjectiveOfModification: string;
    detailedDescriptionOfRequestedChange: string;
    evaluatedBy: string;
    approvedBy: string;
    notedBy: string;
    remarks: string;
};

/** URL to download the required Systems Development Survey Form (PDF). */
const SYSTEMS_DEVELOPMENT_SURVEY_FORM_PDF_URL = '/storage/Forms/Systems%20Development%20Survey%20Form.pdf';
/** URL to download the Access Rights Enrolment Form (PDF). */
const ACCESS_RIGHTS_ENROLMENT_FORM_PDF_URL = '/storage/Forms/Access%20Rights%20Enrolment%20Form.pdf';
/** URL to download the System Issue Report Form (PDF). */
const SYSTEM_ISSUE_REPORT_FORM_PDF_URL = '/storage/Forms/System%20Issue%20Report%20Form.pdf';
/** URL to download the System Change Request Form (PDF) for System Modification requests. */
const SYSTEM_CHANGE_REQUEST_FORM_PDF_URL = '/storage/Forms/System%20Change%20Request%20Form.docx.pdf';

type SystemIssueReport = {
    controlNumber: string;
    requestingDepartment: string;
    dateFiled: string;
    requestingEmployee: string;
    employeeContactNo: string;
    employeeId: string;
    signatureOfEmployee: string;
    natureOfAppointment: string;
    natureOfAppointmentOthersSpecify: string;
    coTerminusUntilDate: string;
    nameOfSoftware: string;
    typeOfRequest: string[];
    typeOfRequestOthersSpecify: string;
    errorSummaryTitle: string;
    detailedDescription: string;
};

const props = withDefaults(
    defineProps<{
        controlTicketNumber: string;
        natureOfRequests: NatureOption[];
        preSelectedNatureId?: number | null;
        canSubmitAsPrivilegedRequester: boolean;
        isSubmitOnlyUser: boolean;
        requesterName?: string | null;
        defaultOfficeDivision?: string | null;
        officeOptions: OfficeOption[];
        officeUsers: OfficeUser[];
        systemsEngineerOptions: SystemsEngineerOption[];
        maxAttachments: number;
        maxAttachmentSizeMb: number;
        qrCodePattern: string;
    }>(),
    { preSelectedNatureId: null },
);

const breadcrumbs = computed<BreadcrumbItem[]>(() => {
    if (props.isSubmitOnlyUser) {
        return [
            {
                title: 'Submit a Ticket Request',
                href: '/submit-request',
            },
        ];
    }

    return [
        {
            title: 'Dashboard',
            href: dashboard().url,
        },
        {
            title: 'Submit a Ticket Request',
            href: '/submit-request',
        },
    ];
});

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
    personalEmail: '',
    officeEmail: '',
    description: '',
    hasQrCode: false,
    qrCodeNumber: '',
    attachments: [] as File[],
    systemDevelopmentSurveyFormAttachments: {} as Record<string, File>,
    systemChangeRequestFormAttachments: {} as Record<string, File>,
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
    systemChangeRequestForm: {
        controlNumber: props.controlTicketNumber,
        date: new Date().toISOString().slice(0, 10),
        officeDivision: props.defaultOfficeDivision ?? '',
        requestedByName: props.requesterName ?? '',
        nameOfSoftware: '',
        typeOfRequest: '',
        descriptionOfRequest: '',
        purposeObjectiveOfModification: '',
        detailedDescriptionOfRequestedChange: '',
        evaluatedBy: '',
        approvedBy: '',
        notedBy: '',
        remarks: '',
    } as SystemChangeRequestForm,
    systemIssueReport: {
        controlNumber: props.controlTicketNumber,
        requestingDepartment: props.defaultOfficeDivision ?? '',
        dateFiled: new Date().toISOString().slice(0, 10),
        requestingEmployee: props.requesterName ?? '',
        employeeContactNo: '',
        employeeId: '',
        signatureOfEmployee: '',
        natureOfAppointment: '',
        natureOfAppointmentOthersSpecify: '',
        coTerminusUntilDate: '',
        nameOfSoftware: '',
        typeOfRequest: [] as string[],
        typeOfRequestOthersSpecify: '',
        errorSummaryTitle: '',
        detailedDescription: '',
    } as SystemIssueReport,
});

const attachmentEntries = ref<AttachmentEntry[]>([]);
const fileInputRef = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const uploadError = ref('');
const officeSearchQuery = ref('');
const userSearchQuery = ref('');
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
    if (!props.canSubmitAsPrivilegedRequester || !submitAttempted.value) {
        return '';
    }
    // Submit Only users may submit on their own behalf without selecting office/user.
    if (props.isSubmitOnlyUser) {
        return '';
    }
    return form.officeDesignationId ? '' : 'Please select an office designation.';
});
const requestedUserError = computed(() => {
    if (!props.canSubmitAsPrivilegedRequester || !submitAttempted.value) {
        return '';
    }
    if (props.isSubmitOnlyUser) {
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

const isAccessRightsEnrolment = computed(() => {
    const normalized = selectedNatureName.value.trim().toLowerCase();
    return (
        normalized === 'rights enrollment form' ||
        normalized === 'rights enrolment form' ||
        normalized.includes('rights enrollment') ||
        normalized.includes('rights enrolment') ||
        normalized.includes('access rights')
    );
});

const isSystemModification = computed(() => {
    return selectedNatureName.value.trim().toLowerCase() === 'system modification';
});

const isSystemErrorBugReport = computed(() => {
    return selectedNatureName.value.trim().toLowerCase() === 'system error / bug report';
});

const isPasswordResetOrAccountRecovery = computed(() => {
    const normalized = selectedNatureName.value.trim().toLowerCase();
    return normalized.includes('password reset') || normalized.includes('account recovery');
});

const isSystemAccountCreation = computed(() => {
    return selectedNatureName.value.trim().toLowerCase() === 'system account creation';
});

/** No longer used for System Development (upload-only workflow). */
const surveyErrors = computed(() => ({}));

const personalEmailError = computed(() => {
    if (!isPasswordResetOrAccountRecovery.value) {
        return '';
    }
    if (!submitAttempted.value) {
        return '';
    }
    if (!form.personalEmail.trim()) {
        return 'Personal email is required for password reset/account recovery requests.';
    }
    return '';
});

const officeEmailError = computed(() => {
    if (!isSystemAccountCreation.value) {
        return '';
    }
    if (!submitAttempted.value) {
        return '';
    }
    if (!form.officeEmail.trim()) {
        return 'Office email is required for system account creation requests.';
    }
    return '';
});

const systemDevelopmentFormUploadErrors = computed(() => {
    if (!submitAttempted.value || !isSystemDevelopment.value) {
        return {};
    }
    const attachments = form.systemDevelopmentSurveyFormAttachments;
    const hasFile = attachments && typeof attachments === 'object' && Object.keys(attachments).length > 0
        && Object.values(attachments).some((v) => v instanceof File);
    if (hasFile) {
        return {};
    }
    return {
        'systemDevelopmentSurveyFormAttachments.0':
            'Completed Systems Development Survey Form (PDF) is required. Download the form above, complete it offline, then upload it here.',
    };
});

const systemChangeRequestFormUploadErrors = computed(() => {
    if (!submitAttempted.value || !isSystemModification.value) {
        return {};
    }
    const attachments = form.systemChangeRequestFormAttachments;
    const hasFile = attachments && typeof attachments === 'object' && Object.keys(attachments).length > 0
        && Object.values(attachments).some((v) => v instanceof File);
    if (hasFile) {
        return {};
    }
    return {
        'systemChangeRequestFormAttachments.0':
            'Completed System Change Request Form (PDF) is required. Download the form above, complete it offline, then upload it here.',
    };
});

/** System Issue Report in-page form removed; use download + description only. No validation. */
const systemIssueReportErrors = computed(() => ({}));

const filteredOfficeUsers = computed(() => {
    if (!form.officeDesignationId) {
        return [];
    }
    const normalizedQuery = userSearchQuery.value.trim().toLowerCase();

    return props.officeUsers
        .filter(
            (user) => String(user.office_designation_id) === String(form.officeDesignationId),
        )
        .filter((user) =>
            normalizedQuery === '' ? true : user.name.toLowerCase().includes(normalizedQuery),
        );
});

const filteredOfficeOptions = computed(() => {
    const normalizedQuery = officeSearchQuery.value.trim().toLowerCase();
    if (normalizedQuery === '') {
        return props.officeOptions;
    }

    return props.officeOptions.filter((office) =>
        office.name.toLowerCase().includes(normalizedQuery),
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
        userSearchQuery.value = '';
        form.clearErrors('requestedForUserId');
    },
);

watch(
    () => [form.officeDesignationId, form.requestedForUserId],
    () => {
        const officeName = props.officeOptions.find(
            (option) => String(option.id) === String(form.officeDesignationId),
        )?.name;

        if (props.canSubmitAsPrivilegedRequester) {
            form.systemDevelopmentSurvey.officeEndUser = officeName ?? '';
        }
    },
);

watch(
    () => form.natureOfRequestId,
    () => {
        form.personalEmail = '';
        form.officeEmail = '';
        form.clearErrors('personalEmail');
        form.clearErrors('officeEmail');
        form.clearErrors('systemDevelopmentSurvey');
        form.clearErrors('systemChangeRequestForm');
        form.clearErrors('systemIssueReport');
    },
);

watch(
    () => isPasswordResetOrAccountRecovery.value,
    (isRecovery) => {
        if (isRecovery) {
            form.description = 'Password reset or account recovery request.';
        }
    },
);

watch(
    () => isSystemDevelopment.value,
    (enabled) => {
        if (!enabled) {
            return;
        }

        attachmentEntries.value = [];
        form.attachments = [];
        uploadError.value = '';

        if (!props.canSubmitAsPrivilegedRequester) {
            form.systemDevelopmentSurvey.assignedSystemsEngineer = '';
            form.systemDevelopmentSurvey.targetCompletion = '';
        }
    },
);

watch(
    () => isSystemErrorBugReport.value,
    (enabled) => {
        if (!enabled) {
            return;
        }
        form.systemIssueReport.controlNumber = props.controlTicketNumber;
        form.systemIssueReport.dateFiled = new Date().toISOString().slice(0, 10);
        form.systemIssueReport.requestingDepartment = props.defaultOfficeDivision ?? '';
        form.systemIssueReport.requestingEmployee = props.requesterName ?? '';
        if (!props.canSubmitAsPrivilegedRequester) {
            return;
        }
        const officeName = props.officeOptions.find(
            (o) => String(o.id) === String(form.officeDesignationId),
        )?.name;
        const selectedUser = props.officeUsers.find(
            (u) => String(u.id) === String(form.requestedForUserId),
        )?.name;
        if (officeName) {
            form.systemIssueReport.requestingDepartment = officeName;
        }
        if (selectedUser) {
            form.systemIssueReport.requestingEmployee = selectedUser;
        }
    },
);

watch(
    () => [form.officeDesignationId, form.requestedForUserId],
    () => {
        if (!isSystemErrorBugReport.value || !props.canSubmitAsPrivilegedRequester) {
            return;
        }
        const officeName = props.officeOptions.find(
            (o) => String(o.id) === String(form.officeDesignationId),
        )?.name;
        const selectedUser = props.officeUsers.find(
            (u) => String(u.id) === String(form.requestedForUserId),
        )?.name;
        if (officeName) {
            form.systemIssueReport.requestingDepartment = officeName;
        }
        if (selectedUser) {
            form.systemIssueReport.requestingEmployee = selectedUser;
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
        personalEmailError.value ||
        officeEmailError.value ||
        descriptionError.value ||
        officeError.value ||
        requestedUserError.value ||
        Object.keys(surveyErrors.value).length > 0 ||
        Object.keys(systemDevelopmentFormUploadErrors.value).length > 0 ||
        Object.keys(systemChangeRequestFormUploadErrors.value).length > 0 ||
        Object.keys(systemIssueReportErrors.value).length > 0
    ) {
        if (natureError.value) {
            form.setError('natureOfRequestId', natureError.value);
        }
        if (personalEmailError.value) {
            form.setError('personalEmail', personalEmailError.value);
        }
        if (officeEmailError.value) {
            form.setError('officeEmail', officeEmailError.value);
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
            form.setError(key as never, String(message));
        });
        Object.entries(systemDevelopmentFormUploadErrors.value).forEach(([key, message]) => {
            form.setError(key as never, String(message));
        });
        Object.entries(systemChangeRequestFormUploadErrors.value).forEach(([key, message]) => {
            form.setError(key as never, String(message));
        });
        Object.entries(systemIssueReportErrors.value).forEach(([key, message]) => {
            form.setError(key as never, String(message));
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
        <div class="submit-request-theme -mx-6 -mt-20 min-h-screen bg-background text-foreground">
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
                    class="rounded-2xl border border-(--miso-form-border) bg-(--miso-form-surface) p-5 text-card-foreground shadow-(--miso-form-shadow) md:p-6"
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
                            v-if="canSubmitAsPrivilegedRequester"
                            class="grid gap-4 rounded-xl border border-(--miso-form-border) bg-(--miso-form-muted) p-4 md:grid-cols-2"
                        >
                            <div class="grid gap-3">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                    Office Designation
                                </label>
                                <input
                                    v-model="officeSearchQuery"
                                    type="text"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                    placeholder="Type to search office..."
                                />
                                <div
                                    v-if="filteredOfficeOptions.length"
                                    class="max-h-40 overflow-y-auto rounded-md border border-input bg-background text-sm"
                                >
                                    <button
                                        v-for="option in filteredOfficeOptions"
                                        :key="option.id"
                                        type="button"
                                        class="flex w-full items-center justify-between px-3 py-1.5 text-left hover:bg-muted"
                                        :class="{
                                            'bg-primary/10 font-semibold':
                                                String(form.officeDesignationId) === String(option.id),
                                        }"
                                        @click="form.officeDesignationId = String(option.id)"
                                    >
                                        <span>{{ option.name }}</span>
                                    </button>
                                </div>
                                <p
                                    v-if="form.officeDesignationId"
                                    class="text-xs text-muted-foreground"
                                >
                                    Selected office:
                                    {{
                                        filteredOfficeOptions.find(
                                            (o) => String(o.id) === String(form.officeDesignationId),
                                        )?.name ||
                                            officeOptions.find(
                                                (o) => String(o.id) === String(form.officeDesignationId),
                                            )?.name
                                    }}
                                </p>
                                <p v-if="form.errors.officeDesignationId || officeError" class="text-xs text-destructive">
                                    {{ form.errors.officeDesignationId || officeError }}
                                </p>
                            </div>

                            <div class="grid gap-3">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                    Requesting For
                                </label>
                                <input
                                    v-model="userSearchQuery"
                                    type="text"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground disabled:opacity-70 focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                    placeholder="Type to search staff..."
                                    :disabled="!form.officeDesignationId"
                                />
                                <div
                                    v-if="form.officeDesignationId && filteredOfficeUsers.length"
                                    class="max-h-40 overflow-y-auto rounded-md border border-input bg-background text-sm disabled:opacity-70"
                                >
                                    <button
                                        v-for="user in filteredOfficeUsers"
                                        :key="user.id"
                                        type="button"
                                        class="flex w-full items-center justify-between px-3 py-1.5 text-left hover:bg-muted"
                                        :class="{
                                            'bg-primary/10 font-semibold':
                                                String(form.requestedForUserId) === String(user.id),
                                        }"
                                        @click="form.requestedForUserId = String(user.id)"
                                    >
                                        <span>{{ user.name }}</span>
                                    </button>
                                </div>
                                <p
                                    v-if="form.requestedForUserId"
                                    class="text-xs text-muted-foreground"
                                >
                                    Selected user:
                                    {{
                                        filteredOfficeUsers.find(
                                            (u) => String(u.id) === String(form.requestedForUserId),
                                        )?.name ||
                                            officeUsers.find(
                                                (u) => String(u.id) === String(form.requestedForUserId),
                                            )?.name
                                    }}
                                </p>
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

                            <div
                                class="grid gap-3 rounded-xl border border-(--miso-form-border) bg-(--miso-form-muted) p-4"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                            QR Code for Unit
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            Only admins/super admin can add a MIS-UID. If no QR code UID is available, an admin can assign one later.
                                        </p>
                                    </div>
                                    <label
                                        class="relative inline-flex cursor-pointer items-center focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-ring rounded"
                                    >
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
                                        QR Code Number (MIS-UID)
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
                            v-if="isPasswordResetOrAccountRecovery"
                            class="grid gap-3 rounded-xl border border-border bg-muted/30 p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                Personal Email (Required)
                            </label>
                            <input
                                v-model="form.personalEmail"
                                type="email"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                placeholder="you@example.com"
                                required
                            />
                            <p v-if="form.errors.personalEmail || personalEmailError" class="text-xs text-destructive">
                                {{ form.errors.personalEmail || personalEmailError }}
                            </p>
                            <p v-else class="text-xs text-muted-foreground">
                                We’ll use this to verify and contact you for account recovery.
                            </p>
                        </div>

                        <div
                            v-if="isSystemAccountCreation"
                            class="grid gap-3 rounded-xl border border-border bg-muted/30 p-4 dark:border-white/10 dark:bg-white/5"
                        >
                            <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                Office Email (Required)
                            </label>
                            <input
                                v-model="form.officeEmail"
                                type="email"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                                placeholder="name@agency.gov.ph"
                                required
                            />
                            <p v-if="form.errors.officeEmail || officeEmailError" class="text-xs text-destructive">
                                {{ form.errors.officeEmail || officeEmailError }}
                            </p>
                            <p v-else class="text-xs text-muted-foreground">
                                Enter the office or government email for the new account.
                            </p>
                        </div>

                        <div
                            v-if="isSystemDevelopment"
                            class="grid gap-5 rounded-2xl border border-border bg-muted/20 p-5 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="space-y-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                    Systems Development Survey Form (Required)
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Download the form below, complete it offline, then upload the completed form before submitting your request.
                                </p>
                                <a
                                    :href="SYSTEMS_DEVELOPMENT_SURVEY_FORM_PDF_URL"
                                    download
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 rounded-md border border-border bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dark:border-white/10"
                                >
                                    Download Systems Development Survey Form (PDF)
                                </a>
                            </div>

                            <div class="grid gap-2">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                    Upload completed form (required)
                                </label>
                                <input
                                    type="file"
                                    accept=".pdf"
                                    class="block w-full text-sm text-foreground file:mr-4 file:rounded-md file:border-0 file:bg-muted file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-[0.2em] file:text-foreground hover:file:bg-muted/70"
                                    @change="(e) => {
                                        const target = e.target as HTMLInputElement;
                                        const file = target.files?.[0];
                                        if (file) {
                                            form.systemDevelopmentSurveyFormAttachments = { '0': file };
                                        } else {
                                            form.systemDevelopmentSurveyFormAttachments = {};
                                        }
                                    }"
                                />
                                <p
                                    v-if="form.errors['systemDevelopmentSurveyFormAttachments'] || form.errors['systemDevelopmentSurveyFormAttachments.0'] || systemDevelopmentFormUploadErrors['systemDevelopmentSurveyFormAttachments.0']"
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors['systemDevelopmentSurveyFormAttachments'] || form.errors['systemDevelopmentSurveyFormAttachments.0'] || systemDevelopmentFormUploadErrors['systemDevelopmentSurveyFormAttachments.0'] }}
                                </p>
                                <p v-else class="text-xs text-muted-foreground">
                                    Upload the completed PDF. Only PDF is accepted.
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="isAccessRightsEnrolment"
                            class="grid gap-5 rounded-2xl border border-border bg-muted/20 p-5 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="space-y-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                    Access Rights Enrolment Form (PDF)
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Download the form below and complete it offline.
                                </p>
                                <a
                                    :href="ACCESS_RIGHTS_ENROLMENT_FORM_PDF_URL"
                                    download
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 rounded-md border border-border bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dark:border-white/10"
                                >
                                    Download Access Rights Enrolment Form (PDF)
                                </a>
                            </div>
                        </div>

                        <div
                            v-if="isSystemModification"
                            class="grid gap-5 rounded-2xl border border-border bg-muted/20 p-5 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="space-y-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                    System Modification Request – System Change Request Form (Required)
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Download the form below, complete it offline, then upload the completed form before submitting your request.
                                </p>
                                <a
                                    :href="SYSTEM_CHANGE_REQUEST_FORM_PDF_URL"
                                    download
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 rounded-md border border-border bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dark:border-white/10"
                                >
                                    Download System Change Request Form (PDF)
                                </a>
                            </div>

                            <div class="grid gap-2">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                    Upload completed form (required)
                                </label>
                                <input
                                    type="file"
                                    accept=".pdf"
                                    class="block w-full text-sm text-foreground file:mr-4 file:rounded-md file:border-0 file:bg-muted file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-[0.2em] file:text-foreground hover:file:bg-muted/70"
                                    @change="(e) => {
                                        const target = e.target as HTMLInputElement;
                                        const file = target.files?.[0];
                                        if (file) {
                                            form.systemChangeRequestFormAttachments = { '0': file };
                                        } else {
                                            form.systemChangeRequestFormAttachments = {};
                                        }
                                    }"
                                />
                                <p
                                    v-if="form.errors['systemChangeRequestFormAttachments'] || form.errors['systemChangeRequestFormAttachments.0'] || systemChangeRequestFormUploadErrors['systemChangeRequestFormAttachments.0']"
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors['systemChangeRequestFormAttachments'] || form.errors['systemChangeRequestFormAttachments.0'] || systemChangeRequestFormUploadErrors['systemChangeRequestFormAttachments.0'] }}
                                </p>
                                <p v-else class="text-xs text-muted-foreground">
                                    Upload the completed PDF. Only PDF is accepted.
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="isSystemErrorBugReport"
                            class="grid gap-5 rounded-2xl border border-border bg-muted/20 p-5 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                    System Issue Report Form (Required)
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    This form must be completed before the ticket can be submitted. You may download the PDF form below for reference.
                                </p>
                                <a
                                    :href="SYSTEM_ISSUE_REPORT_FORM_PDF_URL"
                                    download
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 rounded-md border border-border bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dark:border-white/10"
                                >
                                    Download System Issue Report Form (PDF)
                                </a>
                            </div>
                        </div>

                        <div
                            v-if="!isSystemModification && !isPasswordResetOrAccountRecovery"
                            class="grid gap-3"
                        >
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

                        <div v-if="!isSystemDevelopment && !isPasswordResetOrAccountRecovery" class="grid gap-3">
                            <div class="flex items-center justify-between">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                    Upload Photo/Videos Here (optional)
                                </label>
                                <span class="text-xs text-muted-foreground">
                                    {{ attachmentEntries.length }}/{{ maxAttachments }} files
                                </span>
                            </div>
                            <div
                                class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-(--miso-form-border) bg-(--miso-form-dropzone) px-4 py-4 text-center transition"
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

<style scoped>
.submit-request-theme {
    --miso-form-surface: var(--card);
    --miso-form-border: color-mix(in oklab, var(--primary) 20%, var(--border));
    --miso-form-muted: color-mix(in oklab, var(--primary) 7%, var(--background));
    --miso-form-dropzone: color-mix(in oklab, var(--primary) 9%, var(--muted));
    --miso-form-shadow: color-mix(in oklab, var(--primary) 18%, transparent);
}

.dark .submit-request-theme {
    --miso-form-surface: color-mix(in oklab, var(--card) 88%, #ffffff 12%);
    --miso-form-border: color-mix(in oklab, var(--primary) 34%, var(--border));
    --miso-form-muted: color-mix(in oklab, var(--primary) 16%, var(--background));
    --miso-form-dropzone: color-mix(in oklab, var(--primary) 19%, var(--muted));
    --miso-form-shadow: color-mix(in oklab, #000000 65%, var(--primary) 35%);
}
</style>
