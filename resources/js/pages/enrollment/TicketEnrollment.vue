<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { BrowserQRCodeReader, type IScannerControls } from '@zxing/browser';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

import TicketEnrollmentForm from '@/components/TicketEnrollmentForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

type EnrollmentPayload = {
    uniqueId: string;
    equipmentName: string;
    equipmentType: string;
    brand: string;
    model: string;
    serialNumber: string;
    assetTag: string;
    supplier: string;
    purchaseDate: string;
    expiryDate: string;
    warrantyStatus: string;
    equipmentImageUrls: string[];
    equipmentImages: File[];
    specification: {
        memory: string;
        storage: string;
        operatingSystem: string;
        networkAddress: string;
        accessories: string;
    };
    locationAssignment: {
        assignedTo: string;
        officeDivision: string;
        dateIssued: string;
    };
    requestHistory: {
        natureOfRequest: string;
        date: string;
        actionTaken: string;
        assignedStaff: string;
        remarks: string;
    };
    scheduledMaintenance: {
        date: string;
        remarks: string;
    };
    sections: {
        specification: boolean;
        locationAssignment: boolean;
        requestHistory: boolean;
        scheduledMaintenance: boolean;
    };
};

type NatureOption = {
    id: number;
    name: string;
};

type ReferenceOption = {
    id: number;
    name: string;
};

type ReferenceOptions = {
    status: ReferenceOption[];
    category: ReferenceOption[];
    officeDesignation: ReferenceOption[];
    remarks: ReferenceOption[];
};

const props = defineProps<{
    mode: 'create' | 'edit';
    record?: EnrollmentPayload;
    prefillId?: string;
    natureOfRequests: NatureOption[];
    referenceOptions: ReferenceOptions;
}>();

const record = computed(() => props.record);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Inventory',
        href: '/inventory',
    },
    {
        title: props.mode === 'edit' ? 'Edit Enrollment' : 'Ticket Enrollment',
        href: '/admin/enrollments/create',
    },
]);

const prefillId = props.prefillId ?? '';
const hasPrefill = Boolean(prefillId);
const enrollmentStep = ref<'scan' | 'manual' | 'form'>(
    props.mode === 'edit' ? 'form' : hasPrefill ? 'form' : 'scan',
);
const scannedId = ref(props.record?.uniqueId ?? prefillId);
const manualId = ref('');
const scanError = ref('');
const isScanning = ref(false);
const scanControls = ref<IScannerControls | null>(null);
const videoRef = ref<HTMLVideoElement | null>(null);
const natureOptions = ref<NatureOption[]>(props.natureOfRequests ?? []);
const referenceOptions = ref<ReferenceOptions>(props.referenceOptions ?? {
    status: [],
    category: [],
    officeDesignation: [],
    remarks: [],
});
let natureRefreshTimer: ReturnType<typeof setInterval> | null = null;
let referenceRefreshTimer: ReturnType<typeof setInterval> | null = null;

const isEditMode = computed(() => props.mode === 'edit');
const isReadonlyId = computed(
    () => isEditMode.value || Boolean(scannedId.value),
);
const hasErrors = computed(() => Object.keys(submission.errors).length > 0);
const errorList = computed(() => Object.values(submission.errors));

const submission = useForm<EnrollmentPayload>({
    uniqueId: props.record?.uniqueId ?? props.prefillId ?? '',
    equipmentName: props.record?.equipmentName ?? '',
    equipmentType: props.record?.equipmentType ?? '',
    brand: props.record?.brand ?? '',
    model: props.record?.model ?? '',
    serialNumber: props.record?.serialNumber ?? '',
    assetTag: props.record?.assetTag ?? '',
    supplier: props.record?.supplier ?? '',
    purchaseDate: props.record?.purchaseDate ?? '',
    expiryDate: props.record?.expiryDate ?? '',
    warrantyStatus: props.record?.warrantyStatus ?? '',
    equipmentImageUrls: props.record?.equipmentImageUrls ?? [],
    equipmentImages: [],
    specification: {
        memory: props.record?.specification?.memory ?? '',
        storage: props.record?.specification?.storage ?? '',
        operatingSystem: props.record?.specification?.operatingSystem ?? '',
        networkAddress: props.record?.specification?.networkAddress ?? '',
        accessories: props.record?.specification?.accessories ?? '',
    },
    locationAssignment: {
        assignedTo: props.record?.locationAssignment?.assignedTo ?? '',
        officeDivision: props.record?.locationAssignment?.officeDivision ?? '',
        dateIssued: props.record?.locationAssignment?.dateIssued ?? '',
    },
    requestHistory: {
        natureOfRequest: props.record?.requestHistory?.natureOfRequest ?? '',
        date: props.record?.requestHistory?.date ?? '',
        actionTaken: props.record?.requestHistory?.actionTaken ?? '',
        assignedStaff: props.record?.requestHistory?.assignedStaff ?? '',
        remarks: props.record?.requestHistory?.remarks ?? '',
    },
    scheduledMaintenance: {
        date: props.record?.scheduledMaintenance?.date ?? '',
        remarks: props.record?.scheduledMaintenance?.remarks ?? '',
    },
    sections: {
        specification: props.record?.sections?.specification ?? false,
        locationAssignment: props.record?.sections?.locationAssignment ?? false,
        requestHistory: props.record?.sections?.requestHistory ?? false,
        scheduledMaintenance: props.record?.sections?.scheduledMaintenance ?? false,
    },
});

onMounted(() => {
    if (hasPrefill && !/^MIS-UID-\d{5}$/.test(prefillId)) {
        scanError.value = 'Unique ID must match MIS-UID-00000 format.';
        enrollmentStep.value = 'scan';
        return;
    }
    if (enrollmentStep.value === 'scan') {
        nextTick(() => {
            startScan();
        });
    }

    refreshNatureOptions();
    natureRefreshTimer = setInterval(refreshNatureOptions, 30000);
    window.addEventListener('focus', refreshNatureOptions);

    refreshReferenceOptions();
    referenceRefreshTimer = setInterval(refreshReferenceOptions, 30000);
    window.addEventListener('focus', refreshReferenceOptions);
});

onUnmounted(() => {
    stopScan();
    if (natureRefreshTimer) {
        clearInterval(natureRefreshTimer);
    }
    if (referenceRefreshTimer) {
        clearInterval(referenceRefreshTimer);
    }
    window.removeEventListener('focus', refreshNatureOptions);
    window.removeEventListener('focus', refreshReferenceOptions);
});

function normalizeScannedId(rawValue: string) {
    const match = rawValue.match(/MIS-UID-\d{5}/i);
    return match ? match[0].toUpperCase() : rawValue.trim().toUpperCase();
}

async function validateIssuedUid(value: string) {
    const response = await fetch(
        `/admin/qr-generator/validate/${encodeURIComponent(value)}`,
        {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        },
    );

    if (!response.ok) {
        throw new Error(`Validation failed (${response.status})`);
    }

    const data = (await response.json()) as { valid: boolean };
    return Boolean(data.valid);
}

const cameraConstraintPresets: MediaStreamConstraints[] = [
    {
        video: {
            facingMode: { ideal: 'environment' },
            width: { ideal: 1280 },
            height: { ideal: 720 },
        },
        audio: false,
    },
    {
        video: { facingMode: 'environment' },
        audio: false,
    },
    {
        video: { facingMode: 'user' },
        audio: false,
    },
    { video: true, audio: false },
];

async function startScan() {
    scanError.value = '';
    if (typeof window === 'undefined') {
        scanError.value = 'Camera access is unavailable in this environment.';
        return;
    }

    if (!navigator.mediaDevices?.getUserMedia) {
        scanError.value =
            'Camera access is unavailable in this browser. Enter the ID manually.';
        return;
    }

    if (!videoRef.value) {
        scanError.value = 'Camera feed is unavailable.';
        return;
    }

    const callback = (
        result: { getText: () => string } | null,
        error: { name: string } | null,
    ) => {
        if (!isScanning.value) {
            return;
        }
        if (result) {
            handleUniqueId(normalizeScannedId(result.getText()));
            return;
        }
        if (error && error.name !== 'NotFoundException') {
            scanError.value = 'Unable to read the QR code. Try again.';
            console.error(error);
        }
    };

    try {
        isScanning.value = true;
        const reader = new BrowserQRCodeReader(undefined, {
            delayBetweenScanAttempts: 200,
        });

        for (const constraints of cameraConstraintPresets) {
            try {
                scanControls.value = await reader.decodeFromConstraints(
                    constraints,
                    videoRef.value,
                    callback,
                );
                return;
            } catch {
                if (scanControls.value) {
                    scanControls.value.stop();
                    scanControls.value = null;
                }
                continue;
            }
        }

        scanError.value =
            'Unable to access the camera. Check permissions and try again.';
        isScanning.value = false;
    } catch (error) {
        scanError.value =
            'Unable to access the camera. Check permissions and try again.';
        console.error(error);
        isScanning.value = false;
    }
}

async function refreshNatureOptions() {
    try {
        const response = await fetch('/nature-of-request/options', {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return;
        }

        const data = (await response.json()) as NatureOption[];
        natureOptions.value = data;
    } catch (error) {
        console.error(error);
    }
}

async function refreshReferenceOptions() {
    try {
        const response = await fetch('/reference-values/options', {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return;
        }

        const data = (await response.json()) as ReferenceOptions;
        referenceOptions.value = data;
    } catch (error) {
        console.error(error);
    }
}

function stopScan() {
    isScanning.value = false;
    if (scanControls.value) {
        scanControls.value.stop();
        scanControls.value = null;
    }
    if (videoRef.value?.srcObject) {
        const stream = videoRef.value.srcObject as MediaStream;
        stream.getTracks().forEach((track) => track.stop());
        videoRef.value.srcObject = null;
    }
}

async function handleUniqueId(value: string) {
    scanError.value = '';
    if (!value) {
        scanError.value = 'Enter or scan a valid Unique ID.';
        return;
    }
    if (!/^MIS-UID-\d{5}$/.test(value)) {
        scanError.value = 'Unique ID must match MIS-UID-00000 format.';
        return;
    }

    stopScan();

    try {
        const isIssued = await validateIssuedUid(value);
        if (!isIssued) {
            scanError.value =
                'Invalid UID: This QR code was not generated by the system.';
            return;
        }

        const response = await fetch(
            `/inventory/lookup/${encodeURIComponent(value)}`,
            {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            },
        );
        if (response.redirected) {
            window.location.assign(response.url);
            return;
        }
        if (!response.ok) {
            throw new Error(`Lookup failed (${response.status})`);
        }
        const contentType = response.headers.get('content-type') ?? '';
        if (!contentType.includes('application/json')) {
            throw new Error('Lookup response was not JSON');
        }
        const data = (await response.json()) as {
            exists: boolean;
            redirect?: string;
        };

        if (data.exists && data.redirect) {
            window.location.assign(data.redirect);
            return;
        }

        router.get(
            '/admin/enrollments/create',
            { unique_id: value },
            { replace: true },
        );
    } catch (error) {
        scanError.value = 'Unable to verify this Unique ID. Please try again.';
        console.error(error);
    }
}

function switchToManualEntry() {
    stopScan();
    enrollmentStep.value = 'manual';
    manualId.value = scannedId.value;
}

function startManualCheck() {
    handleUniqueId(normalizeScannedId(manualId.value));
}

function rescan() {
    scannedId.value = '';
    manualId.value = '';
    enrollmentStep.value = 'scan';
    startScan();
}

function submitEnrollment(payload: EnrollmentPayload) {
    submission.clearErrors();
    submission.uniqueId = payload.uniqueId;
    submission.equipmentName = payload.equipmentName;
    submission.equipmentType = payload.equipmentType;
    submission.brand = payload.brand;
    submission.model = payload.model;
    submission.serialNumber = payload.serialNumber;
    submission.assetTag = payload.assetTag;
    submission.supplier = payload.supplier;
    submission.purchaseDate = payload.purchaseDate;
    submission.expiryDate = payload.expiryDate;
    submission.warrantyStatus = payload.warrantyStatus;
    submission.equipmentImageUrls = payload.equipmentImageUrls;
    submission.equipmentImages = payload.equipmentImages;
    submission.specification = payload.specification;
    submission.locationAssignment = payload.locationAssignment;
    submission.requestHistory = payload.requestHistory;
    submission.scheduledMaintenance = payload.scheduledMaintenance;
    submission.sections = payload.sections;

    if (isEditMode.value) {
        submission.put(`/admin/enrollments/${encodeURIComponent(payload.uniqueId)}`);
    } else {
        submission.post('/admin/enrollments');
    }
}
</script>

<template>
    <Head :title="isEditMode ? 'Edit Enrollment' : 'Ticket Enrollment'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <section class="enroll-hero rounded-3xl border border-sidebar-border/60 p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <p class="text-xs uppercase tracking-[0.3em] text-muted-foreground">
                            Inventory System
                        </p>
                        <h1 class="text-2xl font-semibold">
                            {{ isEditMode ? 'Edit equipment enrollment' : 'Ticket enrollment' }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Capture asset identification, optional specifications, and
                            assignment details in a single, organized workflow.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link
                            href="/inventory"
                            class="rounded-full border border-sidebar-border/70 px-4 py-2 text-sm font-semibold text-muted-foreground transition-colors hover:bg-muted/40"
                        >
                            Back to Inventory
                        </Link>
                        <Link
                            v-if="!isEditMode"
                            href="/admin/qr-generator"
                            class="rounded-full bg-[#1e293b] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#0f172a]"
                        >
                            Generate QR IDs
                        </Link>
                    </div>
                </div>
            </section>

            <div v-if="!isEditMode" class="grid gap-6 lg:grid-cols-[360px_1fr]">
                <section class="enroll-side rounded-3xl border border-sidebar-border/60 p-5">
                    <div class="space-y-3">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                            Step 1
                        </h2>
                        <p class="text-base font-semibold">Scan or enter the Unique ID</p>
                        <p class="text-sm text-muted-foreground">
                            The system checks the Inventory database first. If the asset
                            already exists, you will be routed to its details page.
                        </p>
                    </div>

                    <div class="mt-5 rounded-2xl border border-sidebar-border/70 bg-muted/30 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold">QR scanner</p>
                            <span
                                v-if="enrollmentStep === 'scan'"
                                class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700"
                            >
                                Live
                            </span>
                            <span
                                v-else
                                class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600"
                            >
                                Paused
                            </span>
                        </div>
                        <div class="qr-scan-frame mt-4">
                            <video
                                v-show="enrollmentStep === 'scan'"
                                ref="videoRef"
                                class="qr-scan-video"
                                autoplay
                                muted
                                playsinline
                            ></video>
                            <div v-if="enrollmentStep !== 'scan'" class="qr-scan-placeholder">
                                <p class="text-sm font-semibold text-slate-600">
                                    Ready to scan
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Tap rescan to open the camera again.
                                </p>
                            </div>
                        </div>
                        <p v-if="scanError" class="mt-3 text-xs text-red-600">
                            {{ scanError }}
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button
                                v-if="enrollmentStep === 'scan'"
                                type="button"
                                class="rounded-full border border-[#0f172a]/20 px-3 py-1.5 text-xs font-semibold text-[#0f172a] transition-colors hover:bg-[#0f172a]/5 dark:text-white dark:hover:bg-white/10"
                                @click="switchToManualEntry"
                            >
                                Enter ID manually
                            </button>
                            <button
                                v-if="enrollmentStep === 'scan'"
                                type="button"
                                class="rounded-full border border-[#0f172a]/20 px-3 py-1.5 text-xs font-semibold text-[#0f172a] transition-colors hover:bg-[#0f172a]/5 dark:text-white dark:hover:bg-white/10"
                                @click="stopScan"
                            >
                                Stop camera
                            </button>
                            <button
                                v-else
                                type="button"
                                class="rounded-full border border-[#0f172a]/20 px-3 py-1.5 text-xs font-semibold text-[#0f172a] transition-colors hover:bg-[#0f172a]/5 dark:text-white dark:hover:bg-white/10"
                                @click="rescan"
                            >
                                Rescan
                            </button>
                        </div>
                    </div>

                    <div v-if="enrollmentStep === 'manual'" class="mt-5 space-y-3">
                        <label class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                            Manual entry
                        </label>
                        <input
                            v-model="manualId"
                            type="text"
                            class="w-full rounded-2xl border border-sidebar-border/70 bg-background px-4 py-3 text-sm"
                            placeholder="MIS-UID-00000"
                        />
                        <button
                            type="button"
                            class="w-full rounded-full bg-[#0f172a] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#1e293b]"
                            @click="startManualCheck"
                        >
                            Continue
                        </button>
                    </div>
                </section>

                <section class="rounded-3xl border border-sidebar-border/60 bg-background/60 p-5">
                    <div v-if="enrollmentStep !== 'form'" class="space-y-3">
                        <h3 class="text-base font-semibold">Awaiting Unique ID</h3>
                        <p class="text-sm text-muted-foreground">
                            Scan a QR code or enter the ID manually to unlock the enrollment form.
                        </p>
                        <div class="rounded-2xl border border-dashed border-sidebar-border/70 p-6 text-sm text-muted-foreground">
                            The enrollment form will appear here once an unregistered ID is found.
                        </div>
                    </div>
                    <div v-else class="space-y-4">
                        <TicketEnrollmentForm
                            :scanned-id="scannedId"
                            :readonly-id="isReadonlyId"
                            :initial="record"
                            :nature-options="natureOptions"
                            :reference-options="referenceOptions"
                            @submit="submitEnrollment"
                        />
                        <div
                            v-if="hasErrors"
                            class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
                        >
                            <p class="text-sm font-semibold">Please review the highlighted fields.</p>
                            <ul class="mt-2 list-disc pl-4">
                                <li v-for="(error, index) in errorList" :key="`error-${index}`">
                                    {{ error }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>

            <section v-else class="rounded-3xl border border-sidebar-border/60 bg-background/60 p-5">
                <TicketEnrollmentForm
                    :scanned-id="scannedId"
                    :readonly-id="isReadonlyId"
                    :initial="record"
                    :nature-options="natureOptions"
                    :reference-options="referenceOptions"
                    @submit="submitEnrollment"
                />
                <div
                    v-if="hasErrors"
                    class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
                >
                    <p class="text-sm font-semibold">Please review the highlighted fields.</p>
                    <ul class="mt-2 list-disc pl-4">
                        <li v-for="(error, index) in errorList" :key="`error-edit-${index}`">
                            {{ error }}
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style>
.enroll-hero {
    background: linear-gradient(135deg, rgba(226, 232, 240, 0.6), rgba(248, 250, 252, 0.4));
}

.dark .enroll-hero {
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.8), rgba(30, 41, 59, 0.6));
}

.qr-scan-frame {
    position: relative;
    width: 100%;
    aspect-ratio: 4 / 3;
    border-radius: 16px;
    background: #0f172a;
    overflow: hidden;
    box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.2);
}

.qr-scan-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.qr-scan-placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, #e2e8f0, #f8fafc);
    text-align: center;
}

.dark .qr-scan-placeholder {
    background: linear-gradient(145deg, #1e293b, #0f172a);
}
</style>
