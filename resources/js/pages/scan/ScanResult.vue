<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CheckCircle2, ShieldCheck, UserPlus } from 'lucide-vue-next';
import { computed } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type ScanRecord = {
    uniqueId: string;
    equipmentName: string;
    equipmentType: string | null;
    brand: string | null;
    model: string | null;
    serialNumber: string | null;
    assetTag: string | null;
    supplier: string | null;
    purchaseDate: string | null;
    expiryDate: string | null;
    warrantyStatus: string | null;
    equipmentImageUrls: string[];
    specification: {
        memory: string | null;
        storage: string | null;
        operatingSystem: string | null;
        networkAddress: string | null;
        accessories: string | null;
    };
    locationAssignment: {
        assignedTo: string | null;
        officeDivision: string | null;
        dateIssued: string | null;
    };
    requestHistory: {
        natureOfRequest: string | null;
        date: string | null;
        actionTaken: string | null;
        assignedStaff: string | null;
        remarks: string | null;
    };
    scheduledMaintenance: {
        date: string | null;
        remarks: string | null;
    };
    archivedAt: string | null;
    repairStatus: string | null;
    repairComments: string | null;
    acceptedForRepairAt: string | null;
    assignedAdmin: { id: number; name: string; email: string } | null;
};

const props = defineProps<{
    item: ScanRecord;
    status: 'active' | 'archived';
    canReview: boolean;
    canAssign: boolean;
    availableAdmins: { id: number; name: string; email: string }[];
    assignmentNotice?: string | null;
}>();

const imageUrls = computed(() =>
    props.item.equipmentImageUrls.map((image) =>
        image.startsWith('http') ? image : `/storage/${image}`,
    ),
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Scanner',
        href: '/scan',
    },
    {
        title: props.item.uniqueId,
        href: `/scan/${props.item.uniqueId}`,
    },
];

const reviewForm = useForm({
    acceptRepair: true,
    comments: props.item.repairComments ?? '',
});

const assignForm = useForm({
    assignedAdminId: props.item.assignedAdmin?.id ?? '',
});

const hasSpecification = computed(() =>
    Boolean(
        props.item.specification.memory ||
            props.item.specification.storage ||
            props.item.specification.operatingSystem ||
            props.item.specification.networkAddress ||
            props.item.specification.accessories,
    ),
);
const hasLocation = computed(() =>
    Boolean(
        props.item.locationAssignment.assignedTo ||
            props.item.locationAssignment.officeDivision ||
            props.item.locationAssignment.dateIssued,
    ),
);
const hasMaintenance = computed(() =>
    Boolean(
        props.item.scheduledMaintenance.date ||
            props.item.scheduledMaintenance.remarks,
    ),
);

function submitReview() {
    reviewForm.post(`/scan/${props.item.uniqueId}/review`, {
        preserveScroll: true,
    });
}

function submitAssignment() {
    assignForm.put(`/scan/${props.item.uniqueId}/assign`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`${item.equipmentName} | Scan Result`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <section class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                <div
                    v-if="assignmentNotice"
                    class="mb-4 rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-700 dark:text-amber-200"
                >
                    {{ assignmentNotice }}
                </div>
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <p class="text-xs uppercase tracking-[0.3em] text-muted-foreground">
                            Scan result
                        </p>
                        <h1 class="text-2xl font-semibold">{{ item.equipmentName }}</h1>
                        <p class="text-sm text-muted-foreground">
                            Unique ID {{ item.uniqueId }}
                            <span v-if="item.brand"> • {{ item.brand }}</span>
                            <span v-if="item.model"> • {{ item.model }}</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <span
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                            :class="status === 'active'
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200'
                                : 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200'"
                        >
                            {{ status === 'active' ? 'Active asset' : 'Archived asset' }}
                        </span>
                        <span
                            v-if="item.repairStatus === 'accepted'"
                            class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/20 dark:text-blue-200"
                        >
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            Accepted for repair
                        </span>
                        <Link
                            v-if="status === 'active' && canAssign"
                            :href="`/inventory/${item.uniqueId}/edit`"
                            class="rounded-full border border-sidebar-border/70 px-4 py-2 text-sm font-semibold text-muted-foreground transition-colors hover:bg-muted/40"
                        >
                            Edit enrollment
                        </Link>
                        <Link
                            href="/scan"
                            class="rounded-full border border-sidebar-border/70 px-4 py-2 text-sm font-semibold text-muted-foreground transition-colors hover:bg-muted/40"
                        >
                            Scan another
                        </Link>
                    </div>
                </div>
            </section>

            <section v-if="status === 'active' && (canReview || canAssign)" class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-6">
                    <h2 class="text-base font-semibold">Repair review</h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Mark the record as accepted for repair and record remarks.
                    </p>

                    <div v-if="canReview" class="mt-4 space-y-3">
                        <textarea
                            v-model="reviewForm.comments"
                            rows="4"
                            placeholder="Add repair remarks..."
                            class="w-full rounded-xl border border-sidebar-border/70 bg-background px-3 py-2 text-sm"
                        ></textarea>
                        <button
                            type="button"
                            class="w-full rounded-xl bg-[#2563eb] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#1d4ed8] disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="reviewForm.processing"
                            @click="submitReview"
                        >
                            {{ reviewForm.processing ? 'Saving...' : 'Accept for repair' }}
                        </button>
                        <p v-if="reviewForm.errors.acceptRepair" class="text-sm text-red-500">
                            {{ reviewForm.errors.acceptRepair }}
                        </p>
                        <p v-if="reviewForm.errors.comments" class="text-sm text-red-500">
                            {{ reviewForm.errors.comments }}
                        </p>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">
                        You can view the repair status but cannot update it.
                    </p>

                    <div class="mt-6 rounded-xl bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                        <p>
                            Status:
                            <span class="font-semibold text-foreground">
                                {{ item.repairStatus === 'accepted' ? 'Accepted' : 'Pending' }}
                            </span>
                        </p>
                        <p v-if="item.acceptedForRepairAt" class="mt-1">
                            Accepted on {{ item.acceptedForRepairAt }}
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-6">
                    <h2 class="text-base font-semibold">Assignment</h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Assign this record to an available admin for processing.
                    </p>

                    <div v-if="canAssign" class="mt-4 space-y-3">
                        <div class="relative">
                            <select
                                v-model="assignForm.assignedAdminId"
                                class="w-full appearance-none rounded-xl border border-sidebar-border/70 bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Unassigned</option>
                                <option
                                    v-for="admin in availableAdmins"
                                    :key="admin.id"
                                    :value="admin.id"
                                >
                                    {{ admin.name }} — {{ admin.email }}
                                </option>
                            </select>
                            <UserPlus class="pointer-events-none absolute right-3 top-2.5 h-4 w-4 text-muted-foreground" />
                        </div>
                        <button
                            type="button"
                            class="w-full rounded-xl border border-sidebar-border/70 px-4 py-2.5 text-sm font-semibold text-muted-foreground transition-colors hover:bg-muted/40 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="assignForm.processing"
                            @click="submitAssignment"
                        >
                            {{ assignForm.processing ? 'Saving...' : 'Update assignment' }}
                        </button>
                        <p v-if="assignForm.errors.assignedAdminId" class="text-sm text-red-500">
                            {{ assignForm.errors.assignedAdminId }}
                        </p>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">
                        Only super admins can assign records to admins.
                    </p>

                    <div class="mt-6 rounded-xl bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                        <p class="flex items-center gap-2">
                            <ShieldCheck class="h-4 w-4" />
                            <span class="font-semibold text-foreground">
                                {{ item.assignedAdmin?.name || 'Not assigned' }}
                            </span>
                        </p>
                        <p v-if="item.assignedAdmin?.email" class="mt-1">
                            {{ item.assignedAdmin.email }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        Equipment type
                    </p>
                    <p class="mt-3 text-lg font-semibold">
                        {{ item.equipmentType || 'Not specified' }}
                    </p>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        Serial number
                    </p>
                    <p class="mt-3 text-lg font-semibold">
                        {{ item.serialNumber || 'Not recorded' }}
                    </p>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        Asset tag
                    </p>
                    <p class="mt-3 text-lg font-semibold">
                        {{ item.assetTag || 'Not recorded' }}
                    </p>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-6">
                    <h2 class="text-base font-semibold">Procurement & warranty</h2>
                    <div class="mt-4 grid gap-3 text-sm text-muted-foreground">
                        <p><span class="font-semibold text-foreground">Supplier:</span> {{ item.supplier || 'Not specified' }}</p>
                        <p><span class="font-semibold text-foreground">Purchase date:</span> {{ item.purchaseDate || 'Not recorded' }}</p>
                        <p><span class="font-semibold text-foreground">Expiry date:</span> {{ item.expiryDate || 'Not recorded' }}</p>
                        <p><span class="font-semibold text-foreground">Warranty status:</span> {{ item.warrantyStatus || 'Not recorded' }}</p>
                    </div>
                </div>
                <div class="rounded-2xl border border-sidebar-border/60 bg-background p-6">
                    <h2 class="text-base font-semibold">Equipment image</h2>
                    <div class="mt-4 rounded-xl border border-dashed border-sidebar-border/70 p-4 text-sm text-muted-foreground">
                        <div v-if="imageUrls.length" class="flex flex-wrap gap-3">
                            <img
                                v-for="(image, index) in imageUrls"
                                :key="`image-${index}`"
                                :src="image"
                                :alt="`${item.equipmentName} photo ${index + 1}`"
                                class="h-20 w-20 rounded-xl object-cover"
                            />
                        </div>
                        <p v-else>Images not provided.</p>
                    </div>
                    <p v-if="status === 'archived' && item.archivedAt" class="mt-4 text-xs text-amber-600">
                        Archived on {{ item.archivedAt }}
                    </p>
                </div>
            </section>

            <section v-if="hasSpecification" class="rounded-2xl border border-sidebar-border/60 bg-background p-6">
                <h2 class="text-base font-semibold">Specification</h2>
                <div class="mt-4 grid gap-3 text-sm text-muted-foreground sm:grid-cols-2">
                    <p><span class="font-semibold text-foreground">Memory:</span> {{ item.specification.memory || '—' }}</p>
                    <p><span class="font-semibold text-foreground">Storage:</span> {{ item.specification.storage || '—' }}</p>
                    <p><span class="font-semibold text-foreground">Operating system:</span> {{ item.specification.operatingSystem || '—' }}</p>
                    <p><span class="font-semibold text-foreground">Network/IP:</span> {{ item.specification.networkAddress || '—' }}</p>
                    <p><span class="font-semibold text-foreground">Accessories:</span> {{ item.specification.accessories || '—' }}</p>
                </div>
            </section>

            <section v-if="hasLocation" class="rounded-2xl border border-sidebar-border/60 bg-background p-6">
                <h2 class="text-base font-semibold">Location & Assignment</h2>
                <div class="mt-4 grid gap-3 text-sm text-muted-foreground sm:grid-cols-2">
                    <p><span class="font-semibold text-foreground">Assigned to:</span> {{ item.locationAssignment.assignedTo || '—' }}</p>
                    <p><span class="font-semibold text-foreground">Office/division:</span> {{ item.locationAssignment.officeDivision || '—' }}</p>
                    <p><span class="font-semibold text-foreground">Date issued:</span> {{ item.locationAssignment.dateIssued || '—' }}</p>
                </div>
            </section>

            <section class="rounded-2xl border border-sidebar-border/60 bg-background p-6">
                <h2 class="text-base font-semibold">Request History</h2>
                <div
                    v-if="item.requestHistory.natureOfRequest ||
                        item.requestHistory.date ||
                        item.requestHistory.actionTaken ||
                        item.requestHistory.assignedStaff ||
                        item.requestHistory.remarks"
                    class="mt-4 grid gap-3 text-sm text-muted-foreground sm:grid-cols-2"
                >
                    <p><span class="font-semibold text-foreground">Nature of request:</span> {{ item.requestHistory.natureOfRequest || '—' }}</p>
                    <p><span class="font-semibold text-foreground">Date:</span> {{ item.requestHistory.date || '—' }}</p>
                    <p><span class="font-semibold text-foreground">Action taken:</span> {{ item.requestHistory.actionTaken || '—' }}</p>
                    <p><span class="font-semibold text-foreground">Assigned staff:</span> {{ item.requestHistory.assignedStaff || '—' }}</p>
                    <p><span class="font-semibold text-foreground">Remarks:</span> {{ item.requestHistory.remarks || '—' }}</p>
                </div>
                <p v-else class="mt-3 text-sm text-muted-foreground">
                    No maintenance or service history recorded yet.
                </p>
            </section>

            <section v-if="hasMaintenance" class="rounded-2xl border border-sidebar-border/60 bg-background p-6">
                <h2 class="text-base font-semibold">Scheduled Maintenance</h2>
                <div class="mt-4 grid gap-3 text-sm text-muted-foreground sm:grid-cols-2">
                    <p><span class="font-semibold text-foreground">Date:</span> {{ item.scheduledMaintenance.date || '—' }}</p>
                    <p><span class="font-semibold text-foreground">Remarks:</span> {{ item.scheduledMaintenance.remarks || '—' }}</p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
