<script setup lang="ts">
import { computed, reactive, watch } from 'vue';

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
    isLegacy?: boolean;
};

const props = defineProps<{
    scannedId: string;
    readonlyId?: boolean;
    initial?: Partial<EnrollmentPayload>;
    natureOptions?: NatureOption[];
}>();

const emit = defineEmits<{
    (event: 'submit', payload: EnrollmentPayload): void;
}>();

const form = reactive<EnrollmentPayload>({
    uniqueId: '',
    equipmentName: '',
    equipmentType: '',
    brand: '',
    model: '',
    serialNumber: '',
    assetTag: '',
    supplier: '',
    purchaseDate: '',
    expiryDate: '',
    warrantyStatus: '',
    equipmentImageUrls: [],
    equipmentImages: [],
    specification: {
        memory: '',
        storage: '',
        operatingSystem: '',
        networkAddress: '',
        accessories: '',
    },
    locationAssignment: {
        assignedTo: '',
        officeDivision: '',
        dateIssued: '',
    },
    requestHistory: {
        natureOfRequest: '',
        date: '',
        actionTaken: '',
        assignedStaff: '',
        remarks: '',
    },
    scheduledMaintenance: {
        date: '',
        remarks: '',
    },
    sections: {
        specification: false,
        locationAssignment: false,
        requestHistory: false,
        scheduledMaintenance: false,
    },
});

const isReadonlyId = computed(() => Boolean(props.readonlyId && props.scannedId));
const natureOptionsWithLegacy = computed<NatureOption[]>(() => {
    const options = (props.natureOptions ?? []).map((option) => ({
        ...option,
        isLegacy: false,
    }));
    const currentValue = form.requestHistory.natureOfRequest?.trim();

    if (!currentValue) {
        return options;
    }

    const exists = options.some((option) => option.name === currentValue);

    if (exists) {
        return options;
    }

    return [
        {
            id: -1,
            name: currentValue,
            isLegacy: true,
        },
        ...options,
    ];
});

watch(
    () => props.scannedId,
    (value) => {
        form.uniqueId = value;
    },
    { immediate: true },
);

watch(
    () => props.initial,
    (value) => {
        if (!value) {
            return;
        }

        form.uniqueId = value.uniqueId ?? form.uniqueId;
        form.equipmentName = value.equipmentName ?? form.equipmentName;
        form.equipmentType = value.equipmentType ?? form.equipmentType;
        form.brand = value.brand ?? form.brand;
        form.model = value.model ?? form.model;
        form.serialNumber = value.serialNumber ?? form.serialNumber;
        form.assetTag = value.assetTag ?? form.assetTag;
        form.supplier = value.supplier ?? form.supplier;
        form.purchaseDate = value.purchaseDate ?? form.purchaseDate;
        form.expiryDate = value.expiryDate ?? form.expiryDate;
        form.warrantyStatus = value.warrantyStatus ?? form.warrantyStatus;
        form.equipmentImageUrls =
            value.equipmentImageUrls ?? form.equipmentImageUrls;

        form.specification = {
            ...form.specification,
            ...value.specification,
        };
        form.locationAssignment = {
            ...form.locationAssignment,
            ...value.locationAssignment,
        };
        form.requestHistory = {
            ...form.requestHistory,
            ...value.requestHistory,
        };
        form.scheduledMaintenance = {
            ...form.scheduledMaintenance,
            ...value.scheduledMaintenance,
        };

        const hasSpecification = Boolean(
            value.specification?.memory ||
                value.specification?.storage ||
                value.specification?.operatingSystem ||
                value.specification?.networkAddress ||
                value.specification?.accessories,
        );
        const hasLocation = Boolean(
            value.locationAssignment?.assignedTo ||
                value.locationAssignment?.officeDivision ||
                value.locationAssignment?.dateIssued,
        );
        const hasRequestHistory = Boolean(
            value.requestHistory?.natureOfRequest ||
                value.requestHistory?.date ||
                value.requestHistory?.actionTaken ||
                value.requestHistory?.assignedStaff ||
                value.requestHistory?.remarks,
        );
        const hasMaintenance = Boolean(
            value.scheduledMaintenance?.date || value.scheduledMaintenance?.remarks,
        );

        form.sections = {
            specification: value.sections?.specification ?? hasSpecification,
            locationAssignment: value.sections?.locationAssignment ?? hasLocation,
            requestHistory: value.sections?.requestHistory ?? hasRequestHistory,
            scheduledMaintenance: value.sections?.scheduledMaintenance ?? hasMaintenance,
        };
    },
    { immediate: true },
);

function submit() {
    emit('submit', { ...form });
}
</script>

<template>
    <form class="enroll-form space-y-6" @submit.prevent="submit">
        <section class="enroll-card">
            <div class="enroll-section-header">
                <div>
                    <h3 class="enroll-title">Identification</h3>
                    <p class="enroll-subtitle">Required fields first</p>
                </div>
                <span class="enroll-badge">Asset details</span>
            </div>
            <div class="enroll-grid">
                <div>
                    <label class="enroll-label">
                        Unique ID <span class="enroll-required">*</span>
                    </label>
                    <input
                        v-model="form.uniqueId"
                        type="text"
                        :readonly="isReadonlyId"
                        class="enroll-input"
                        placeholder="MIS-UID-00000"
                        pattern="MIS-UID-[0-9]{5}"
                    />
                </div>
                <div>
                    <label class="enroll-label">
                        Equipment Name <span class="enroll-required">*</span>
                    </label>
                    <input
                        v-model="form.equipmentName"
                        type="text"
                        required
                        class="enroll-input"
                        placeholder="Laptop, printer, router"
                    />
                </div>
                <div>
                    <label class="enroll-label">
                        Equipment Type <span class="enroll-required">*</span>
                    </label>
                    <input
                        v-model="form.equipmentType"
                        type="text"
                        required
                        class="enroll-input"
                        placeholder="Asset category"
                    />
                </div>
                <div>
                    <label class="enroll-label">
                        Brand/Manufacturer <span class="enroll-required">*</span>
                    </label>
                    <input
                        v-model="form.brand"
                        type="text"
                        required
                        class="enroll-input"
                        placeholder="Dell, HP, Cisco"
                    />
                </div>
                <div>
                    <label class="enroll-label">
                        Model/Series <span class="enroll-required">*</span>
                    </label>
                    <input
                        v-model="form.model"
                        type="text"
                        required
                        class="enroll-input"
                        placeholder="Model number"
                    />
                </div>
                <div>
                    <label class="enroll-label">Serial Number</label>
                    <input
                        v-model="form.serialNumber"
                        type="text"
                        class="enroll-input"
                        placeholder="SN-0000"
                    />
                </div>
                <div>
                    <label class="enroll-label">Property/Asset Tag No.</label>
                    <input
                        v-model="form.assetTag"
                        type="text"
                        class="enroll-input"
                        placeholder="TAG-0000"
                    />
                </div>
                <div>
                    <label class="enroll-label">Supplier</label>
                    <input
                        v-model="form.supplier"
                        type="text"
                        class="enroll-input"
                        placeholder="Vendor name"
                    />
                </div>
                <div>
                    <label class="enroll-label">Purchase Date</label>
                    <input v-model="form.purchaseDate" type="date" class="enroll-input" />
                </div>
                <div>
                    <label class="enroll-label">Expiry Date</label>
                    <input v-model="form.expiryDate" type="date" class="enroll-input" />
                </div>
                <div>
                    <label class="enroll-label">Warranty Status</label>
                    <input
                        v-model="form.warrantyStatus"
                        type="text"
                        class="enroll-input"
                        placeholder="Active, expired, extended"
                    />
                </div>
                <div class="sm:col-span-2 xl:col-span-4">
                    <label class="enroll-label">Equipment Images/Pictures</label>
                    <input
                        type="file"
                        class="enroll-input"
                        accept="image/*"
                        multiple
                        capture="environment"
                        @change="
                            form.equipmentImages = Array.from(
                                ($event.target as HTMLInputElement).files || [],
                            )
                        "
                    />
                    <p class="enroll-footnote">
                        Upload or take multiple photos. Existing files remain unless replaced.
                    </p>
                    <div
                        v-if="form.equipmentImageUrls.length"
                        class="mt-2 flex flex-wrap gap-2 text-xs text-muted-foreground"
                    >
                        <span
                            v-for="(image, index) in form.equipmentImageUrls"
                            :key="`image-${index}`"
                            class="rounded-full border border-sidebar-border/60 px-3 py-1"
                        >
                            {{ image }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div class="enroll-card">
                <div class="enroll-section-header">
                    <div>
                        <h4 class="enroll-title">Optional sections</h4>
                        <p class="enroll-subtitle">
                            Include the details that apply to this asset.
                        </p>
                    </div>
                    <span class="enroll-badge">Include this section</span>
                </div>
                <div class="enroll-toggle-grid">
                    <label class="enroll-toggle">
                        <input
                            v-model="form.sections.specification"
                            type="checkbox"
                            class="enroll-checkbox"
                        />
                        <span>Specification</span>
                    </label>
                    <label class="enroll-toggle">
                        <input
                            v-model="form.sections.locationAssignment"
                            type="checkbox"
                            class="enroll-checkbox"
                        />
                        <span>Location &amp; Assignment</span>
                    </label>
                    <label class="enroll-toggle">
                        <input
                            v-model="form.sections.requestHistory"
                            type="checkbox"
                            class="enroll-checkbox"
                        />
                        <span>Request History</span>
                    </label>
                    <label class="enroll-toggle">
                        <input
                            v-model="form.sections.scheduledMaintenance"
                            type="checkbox"
                            class="enroll-checkbox"
                        />
                        <span>Scheduled Maintenance</span>
                    </label>
                </div>
            </div>

            <Transition name="enroll-fade">
                <section v-if="form.sections.specification" class="enroll-panel">
                    <div class="enroll-section-header">
                        <h4 class="enroll-title">Specification</h4>
                        <span class="enroll-badge">Hardware details</span>
                    </div>
                    <div class="enroll-grid enroll-grid--tight">
                        <div>
                            <label class="enroll-label">Memory (RAM)</label>
                            <input
                                v-model="form.specification.memory"
                                type="text"
                                class="enroll-input"
                                placeholder="16 GB"
                            />
                        </div>
                        <div>
                            <label class="enroll-label">Storage</label>
                            <input
                                v-model="form.specification.storage"
                                type="text"
                                class="enroll-input"
                                placeholder="512 GB SSD"
                            />
                        </div>
                        <div>
                            <label class="enroll-label">Operating System</label>
                            <input
                                v-model="form.specification.operatingSystem"
                                type="text"
                                class="enroll-input"
                                placeholder="Windows 11"
                            />
                        </div>
                        <div>
                            <label class="enroll-label">Network/IP Address</label>
                            <input
                                v-model="form.specification.networkAddress"
                                type="text"
                                class="enroll-input"
                                placeholder="10.0.0.12"
                            />
                        </div>
                        <div>
                            <label class="enroll-label">Accessories Included</label>
                            <input
                                v-model="form.specification.accessories"
                                type="text"
                                class="enroll-input"
                                placeholder="Dock, charger, mouse"
                            />
                        </div>
                    </div>
                </section>
            </Transition>

            <Transition name="enroll-fade">
                <section v-if="form.sections.locationAssignment" class="enroll-panel">
                    <div class="enroll-section-header">
                        <h4 class="enroll-title">Location &amp; Assignment</h4>
                        <span class="enroll-badge">Issuance info</span>
                    </div>
                    <div class="enroll-grid enroll-grid--tight">
                        <div>
                            <label class="enroll-label">Assigned To</label>
                            <input
                                v-model="form.locationAssignment.assignedTo"
                                type="text"
                                class="enroll-input"
                                placeholder="Staff name"
                            />
                        </div>
                        <div>
                            <label class="enroll-label">Office/Division</label>
                            <input
                                v-model="form.locationAssignment.officeDivision"
                                type="text"
                                class="enroll-input"
                                placeholder="Unit or team"
                            />
                        </div>
                        <div>
                            <label class="enroll-label">Date Issued</label>
                            <input
                                v-model="form.locationAssignment.dateIssued"
                                type="date"
                                class="enroll-input"
                            />
                        </div>
                    </div>
                </section>
            </Transition>

            <Transition name="enroll-fade">
                <section v-if="form.sections.requestHistory" class="enroll-panel">
                    <div class="enroll-section-header">
                        <h4 class="enroll-title">Request History</h4>
                        <span class="enroll-badge">Service log</span>
                    </div>
                    <div class="enroll-grid enroll-grid--tight">
                        <div>
                            <label class="enroll-label">Nature of Request</label>
                            <select
                                v-model="form.requestHistory.natureOfRequest"
                                class="enroll-input"
                            >
                                <option value="" disabled>Select a request type</option>
                                <option
                                    v-for="option in natureOptionsWithLegacy"
                                    :key="option.isLegacy ? `legacy-${option.name}` : option.id"
                                    :value="option.name"
                                >
                                    {{
                                        option.isLegacy
                                            ? `${option.name} (inactive)`
                                            : option.name
                                    }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="enroll-label">Date</label>
                            <input
                                v-model="form.requestHistory.date"
                                type="date"
                                class="enroll-input"
                            />
                        </div>
                        <div>
                            <label class="enroll-label">Action Taken</label>
                            <input
                                v-model="form.requestHistory.actionTaken"
                                type="text"
                                class="enroll-input"
                                placeholder="Updated firmware"
                            />
                        </div>
                        <div>
                            <label class="enroll-label">Assigned IT Staff</label>
                            <input
                                v-model="form.requestHistory.assignedStaff"
                                type="text"
                                class="enroll-input"
                                placeholder="Technician name"
                            />
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="enroll-label">Remarks/Recommendation</label>
                            <input
                                v-model="form.requestHistory.remarks"
                                type="text"
                                class="enroll-input"
                                placeholder="Notes"
                            />
                        </div>
                    </div>
                </section>
            </Transition>

            <Transition name="enroll-fade">
                <section v-if="form.sections.scheduledMaintenance" class="enroll-panel">
                    <div class="enroll-section-header">
                        <h4 class="enroll-title">Scheduled Maintenance</h4>
                        <span class="enroll-badge">Upcoming work</span>
                    </div>
                    <div class="enroll-grid enroll-grid--tight">
                        <div>
                            <label class="enroll-label">Date</label>
                            <input
                                v-model="form.scheduledMaintenance.date"
                                type="date"
                                class="enroll-input"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="enroll-label">Remarks/Recommendation</label>
                            <input
                                v-model="form.scheduledMaintenance.remarks"
                                type="text"
                                class="enroll-input"
                                placeholder="Upcoming tasks"
                            />
                        </div>
                    </div>
                </section>
            </Transition>
        </section>

        <div class="enroll-footer">
            <button type="submit" class="enroll-submit">Save enrollment</button>
            <p class="enroll-footnote">Sections remain hidden until enabled.</p>
        </div>
    </form>
</template>

<style>
.enroll-form {
    color: inherit;
    min-height: 100vh;
    width: 100%;
    padding: 16px;
}

.enroll-card {
    border-radius: 24px;
    border: 1px solid rgba(148, 163, 184, 0.45);
    background: color-mix(in srgb, var(--background, #ffffff) 92%, #f8fafc);
    padding: 20px;
}

.enroll-section-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.enroll-title {
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.enroll-subtitle {
    margin-top: 4px;
    font-size: 12px;
    color: color-mix(in srgb, currentColor 55%, transparent);
}

.enroll-badge {
    border-radius: 999px;
    padding: 6px 12px;
    background: color-mix(in srgb, #e2e8f0 90%, transparent);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: #475569;
}

.enroll-grid {
    margin-top: 16px;
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

.enroll-grid--tight {
    margin-top: 12px;
}

.enroll-label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: color-mix(in srgb, currentColor 62%, transparent);
}

.enroll-required {
    color: #dc2626;
    font-weight: 700;
}

.enroll-input {
    margin-top: 8px;
    width: 100%;
    border-radius: 14px;
    border: 1px solid rgba(148, 163, 184, 0.6);
    background: color-mix(in srgb, var(--background, #ffffff) 96%, #f8fafc);
    padding: 10px 12px;
    font-size: 14px;
    color: inherit;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.enroll-input:focus {
    outline: none;
    border-color: #1d4ed8;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
}

.enroll-input:read-only {
    background: color-mix(in srgb, #f1f5f9 90%, transparent);
    color: color-mix(in srgb, currentColor 70%, transparent);
}

.enroll-toggle-grid {
    margin-top: 16px;
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

.enroll-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    border-radius: 12px;
    border: 1px dashed rgba(148, 163, 184, 0.6);
    padding: 10px 12px;
    font-size: 13px;
    font-weight: 600;
    color: color-mix(in srgb, currentColor 85%, transparent);
    background: color-mix(in srgb, var(--background, #ffffff) 94%, #eef2ff);
}

.enroll-checkbox {
    height: 16px;
    width: 16px;
    border-radius: 4px;
    border: 1px solid rgba(148, 163, 184, 0.9);
}

.enroll-panel {
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, 0.45);
    background: color-mix(in srgb, var(--background, #ffffff) 90%, #e2e8f0);
    padding: 18px;
}

.enroll-footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    border-radius: 18px;
    border: 1px solid rgba(148, 163, 184, 0.45);
    background: color-mix(in srgb, var(--background, #ffffff) 92%, #f1f5f9);
    padding: 12px 16px;
}

.enroll-submit {
    border-radius: 999px;
    background: #0f172a;
    padding: 10px 18px;
    font-size: 14px;
    font-weight: 700;
    color: #ffffff;
    transition: background 0.2s ease;
}

.enroll-submit:hover {
    background: #1e293b;
}

.enroll-footnote {
    font-size: 12px;
    color: color-mix(in srgb, currentColor 60%, transparent);
}

.enroll-fade-enter-active,
.enroll-fade-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.enroll-fade-enter-from,
.enroll-fade-leave-to {
    opacity: 0;
    transform: translateY(6px);
}

@media (min-width: 640px) {
    .enroll-form {
        padding: 24px;
    }

    .enroll-card,
    .enroll-panel {
        padding: 24px;
    }

    .enroll-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .enroll-toggle-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (min-width: 1024px) {
    .enroll-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .enroll-grid .sm\:col-span-2,
    .enroll-grid .lg\:col-span-3 {
        grid-column: span 3;
    }
}

@media (min-width: 1280px) {
    .enroll-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .enroll-grid .xl\:col-span-4 {
        grid-column: span 4;
    }
}
</style>
