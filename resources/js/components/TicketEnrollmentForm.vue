<script setup lang="ts">
import { computed, reactive, watch } from 'vue';
import SearchableSelect from '@/components/SearchableSelect.vue';

type EnrollmentPayload = {
    uniqueId: string;
    equipmentName: string;
    officeId: number | '';
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

type ReferenceOption = {
    id: number;
    name: string;
    isLegacy?: boolean;
};

type ReferenceOptions = {
    status?: ReferenceOption[];
    equipmentType?: ReferenceOption[];
    officeDesignation?: ReferenceOption[];
    remarks?: ReferenceOption[];
};

const props = defineProps<{
    scannedId: string;
    readonlyId?: boolean;
    initial?: Partial<EnrollmentPayload>;
    natureOptions?: NatureOption[];
    referenceOptions?: ReferenceOptions;
}>();

const emit = defineEmits<{
    (event: 'submit', payload: EnrollmentPayload): void;
}>();

const form = reactive<EnrollmentPayload>({
    uniqueId: '',
    equipmentName: '',
    officeId: '',
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

function withLegacyOption<T extends ReferenceOption>(options: T[], currentValue: string) {
    const normalizedValue = currentValue?.trim();

    if (!normalizedValue) {
        return options.map((option) => ({
            ...option,
            isLegacy: false,
        }));
    }

    const exists = options.some((option) => option.name === normalizedValue);

    if (exists) {
        return options.map((option) => ({
            ...option,
            isLegacy: false,
        }));
    }

    return [
        {
            id: -1,
            name: normalizedValue,
            isLegacy: true,
        },
        ...options.map((option) => ({
            ...option,
            isLegacy: false,
        })),
    ];
}

const categoryOptionsWithLegacy = computed(() =>
    withLegacyOption(props.referenceOptions?.equipmentType ?? [], form.equipmentType),
);

const statusOptionsWithLegacy = computed(() =>
    withLegacyOption(props.referenceOptions?.status ?? [], form.warrantyStatus),
);

const officeOptionsWithLegacy = computed(() =>
    withLegacyOption(
        props.referenceOptions?.officeDesignation ?? [],
        form.locationAssignment.officeDivision,
    ),
);

const selectedOfficeOption = computed(() =>
    officeOptionsWithLegacy.value.find(
        (option) => String(option.id) === String(form.officeId),
    ),
);

const remarkOptionsWithLegacy = computed(() =>
    withLegacyOption(
        props.referenceOptions?.remarks ?? [],
        form.requestHistory.remarks,
    ),
);

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
        form.officeId = value.officeId ?? form.officeId;
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

watch(
    selectedOfficeOption,
    (office) => {
        const officeName = office?.name ?? '';
        form.equipmentName = officeName;
        form.locationAssignment.officeDivision = officeName;
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
                        Office Designation <span class="enroll-required">*</span>
                    </label>
                    <SearchableSelect
                        v-model="form.officeId"
                        :options="officeOptionsWithLegacy"
                        required
                        aria-label="Select office designation"
                        placeholder="Select an office"
                        search-placeholder="Search office designation"
                        empty-label="No office designation found."
                    />
                </div>
                <div>
                    <label class="enroll-label">
                        Equipment Type <span class="enroll-required">*</span>
                    </label>
                    <select
                        v-model="form.equipmentType"
                        required
                        class="enroll-input"
                    >
                        <option value="" disabled>Select an equipment type</option>
                        <option
                            v-for="option in categoryOptionsWithLegacy"
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
                    <select v-model="form.warrantyStatus" class="enroll-input">
                        <option value="">Select a status</option>
                        <option
                            v-for="option in statusOptionsWithLegacy"
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
                                :value="selectedOfficeOption?.name ?? ''"
                                type="text"
                                readonly
                                class="enroll-input"
                                placeholder="Select an office above"
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
                            <select
                                v-model="form.requestHistory.remarks"
                                class="enroll-input"
                            >
                                <option value="">Select a remark</option>
                                <option
                                    v-for="option in remarkOptionsWithLegacy"
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
    --enroll-form-surface: color-mix(in srgb, var(--card) 92%, var(--background));
    --enroll-form-panel: color-mix(in srgb, var(--card) 88%, var(--muted));
    --enroll-form-footer: color-mix(in srgb, var(--card) 90%, var(--muted));
    --enroll-form-border: color-mix(in srgb, var(--border) 82%, transparent);
    --enroll-form-input-bg: color-mix(in srgb, var(--background) 96%, var(--card));
    --enroll-form-input-readonly: color-mix(in srgb, var(--muted) 88%, var(--background));
    --enroll-form-label: color-mix(in srgb, var(--foreground) 72%, transparent);
    --enroll-form-subtitle: var(--muted-foreground);
    --enroll-form-focus: var(--ring);
    --enroll-form-focus-ring: color-mix(in srgb, var(--ring) 24%, transparent);
    --enroll-form-required: color-mix(in srgb, var(--destructive) 90%, #991b1b);
    --enroll-form-chip-bg: color-mix(in srgb, var(--muted) 85%, var(--background));
    --enroll-form-chip-fg: color-mix(in srgb, var(--foreground) 68%, transparent);
    --enroll-form-toggle-bg: color-mix(in srgb, var(--background) 92%, var(--primary) 8%);
    --enroll-form-toggle-border: color-mix(in srgb, var(--border) 80%, var(--primary) 20%);
    --enroll-form-submit-bg: var(--primary);
    --enroll-form-submit-fg: var(--primary-foreground);
    --enroll-form-submit-hover: color-mix(in srgb, var(--primary) 88%, var(--foreground));

    color: inherit;
    min-height: 100vh;
    width: 100%;
    padding: 16px;
}

.dark .enroll-form {
    --enroll-form-surface: color-mix(in srgb, var(--card) 82%, var(--background));
    --enroll-form-panel: color-mix(in srgb, var(--card) 75%, var(--background));
    --enroll-form-footer: color-mix(in srgb, var(--card) 78%, var(--background));
    --enroll-form-border: color-mix(in srgb, var(--border) 88%, transparent);
    --enroll-form-input-bg: color-mix(in srgb, var(--background) 78%, var(--card));
    --enroll-form-input-readonly: color-mix(in srgb, var(--muted) 82%, var(--background));
    --enroll-form-label: color-mix(in srgb, var(--foreground) 82%, transparent);
    --enroll-form-subtitle: color-mix(in srgb, var(--muted-foreground) 92%, var(--foreground) 8%);
    --enroll-form-focus-ring: color-mix(in srgb, var(--ring) 34%, transparent);
    --enroll-form-required: color-mix(in srgb, var(--destructive) 88%, #fecaca);
    --enroll-form-chip-bg: color-mix(in srgb, var(--muted) 72%, var(--background));
    --enroll-form-chip-fg: color-mix(in srgb, var(--foreground) 82%, transparent);
    --enroll-form-toggle-bg: color-mix(in srgb, var(--background) 72%, var(--primary) 16%);
    --enroll-form-toggle-border: color-mix(in srgb, var(--border) 84%, var(--primary) 28%);
    --enroll-form-submit-hover: color-mix(in srgb, var(--primary) 76%, #ffffff 24%);
}

.enroll-card {
    border-radius: 24px;
    border: 1px solid var(--enroll-form-border);
    background: var(--enroll-form-surface);
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
    color: var(--enroll-form-subtitle);
}

.enroll-badge {
    border-radius: 999px;
    padding: 6px 12px;
    background: var(--enroll-form-chip-bg);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: var(--enroll-form-chip-fg);
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
    color: var(--enroll-form-label);
}

.enroll-required {
    color: var(--enroll-form-required);
    font-weight: 700;
}

.enroll-input {
    margin-top: 8px;
    width: 100%;
    border-radius: 14px;
    border: 1px solid var(--enroll-form-border);
    background: var(--enroll-form-input-bg);
    padding: 10px 12px;
    font-size: 14px;
    color: inherit;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.enroll-input:focus {
    outline: none;
    border-color: var(--enroll-form-focus);
    box-shadow: 0 0 0 3px var(--enroll-form-focus-ring);
}

.enroll-input:read-only {
    background: var(--enroll-form-input-readonly);
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
    border: 1px dashed var(--enroll-form-toggle-border);
    padding: 10px 12px;
    font-size: 13px;
    font-weight: 600;
    color: color-mix(in srgb, currentColor 85%, transparent);
    background: var(--enroll-form-toggle-bg);
}

.enroll-checkbox {
    height: 16px;
    width: 16px;
    border-radius: 4px;
    border: 1px solid color-mix(in srgb, var(--enroll-form-border) 90%, var(--foreground) 10%);
}

.enroll-panel {
    border-radius: 20px;
    border: 1px solid var(--enroll-form-border);
    background: var(--enroll-form-panel);
    padding: 18px;
}

.enroll-footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    border-radius: 18px;
    border: 1px solid var(--enroll-form-border);
    background: var(--enroll-form-footer);
    padding: 12px 16px;
}

.enroll-submit {
    border-radius: 999px;
    background: var(--enroll-form-submit-bg);
    padding: 10px 18px;
    font-size: 14px;
    font-weight: 700;
    color: var(--enroll-form-submit-fg);
    transition: background 0.2s ease;
}

.enroll-submit:hover {
    background: var(--enroll-form-submit-hover);
}

.enroll-footnote {
    font-size: 12px;
    color: var(--enroll-form-subtitle);
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
