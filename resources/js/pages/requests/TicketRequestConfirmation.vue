<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type Attachment = {
    name: string;
    size?: number | null;
    url?: string | null;
    mime?: string | null;
};

defineProps<{
    ticket: {
        controlTicketNumber: string;
        natureOfRequest: string | null;
        description: string;
        hasQrCode: boolean;
        qrCodeNumber?: string | null;
        attachments: Attachment[];
        systemDevelopmentSurvey?: Record<string, unknown> | null;
        systemChangeRequestForm?: Record<string, unknown> | null;
        systemIssueReport?: Record<string, unknown> | null;
        systemIssueReportAttachments?: Array<{ name: string; url?: string | null; size?: number | null; mime?: string | null }>;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Ticket Confirmation',
        href: '/requests',
    },
];

function formatSize(size?: number | null) {
    if (!size) {
        return '';
    }
    if (size < 1024) {
        return `${size} B`;
    }
    if (size < 1024 * 1024) {
        return `${(size / 1024).toFixed(1)} KB`;
    }
    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
}
</script>

<template>
    <Head title="Ticket Confirmation" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="-mx-6 -mt-20 min-h-screen bg-[#0b2a4a] text-white">
            <div class="mx-auto flex w-full max-w-4xl flex-col gap-8 px-6 pb-16 pt-14">
                <div class="space-y-3 text-center">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-200/70">
                        Ticket Created
                    </p>
                    <h1 class="text-4xl font-bold md:text-5xl">
                        Request Submitted Successfully
                    </h1>
                    <p class="text-sm text-slate-200/80 md:text-base">
                        Keep your control ticket number for reference and tracking.
                    </p>
                </div>

                <div class="rounded-3xl bg-white/95 p-8 text-slate-900 shadow-2xl shadow-black/20 md:p-10">
                    <div class="grid gap-6">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-6 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                Control Ticket Number
                            </p>
                            <p class="mt-2 text-2xl font-bold text-slate-800">
                                {{ ticket.controlTicketNumber }}
                            </p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 px-5 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                    Nature of Request
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">
                                    {{ ticket.natureOfRequest ?? 'Not provided' }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 px-5 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                    QR Code Number
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">
                                    {{ ticket.hasQrCode ? ticket.qrCodeNumber : 'Not applicable' }}
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 px-5 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                Description of Request
                            </p>
                            <p class="mt-2 text-sm text-slate-700">
                                {{ ticket.description }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 px-5 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                Attachments
                            </p>
                            <div v-if="ticket.attachments.length" class="mt-3 grid gap-3">
                                <div
                                    v-for="attachment in ticket.attachments"
                                    :key="attachment.name"
                                    class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700"
                                >
                                    <div>
                                        <p class="font-semibold">{{ attachment.name }}</p>
                                        <p v-if="attachment.size" class="text-xs text-slate-500">
                                            {{ formatSize(attachment.size) }}
                                        </p>
                                    </div>
                                    <a
                                        v-if="attachment.url"
                                        :href="attachment.url"
                                        target="_blank"
                                        rel="noreferrer"
                                        class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600 hover:text-blue-500"
                                    >
                                        View
                                    </a>
                                </div>
                            </div>
                            <p v-else class="mt-2 text-sm text-slate-500">
                                No attachments were provided.
                            </p>
                        </div>

                        <div
                            v-if="ticket.systemIssueReport"
                            class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4"
                        >
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                System Issue Report
                            </p>
                            <div class="mt-3 grid gap-3 text-sm text-slate-700 sm:grid-cols-2">
                                <div>
                                    <span class="text-slate-500">Control Number:</span>
                                    {{ ticket.systemIssueReport.controlNumber ?? '—' }}
                                </div>
                                <div>
                                    <span class="text-slate-500">Requesting Department:</span>
                                    {{ ticket.systemIssueReport.requestingDepartment ?? '—' }}
                                </div>
                                <div>
                                    <span class="text-slate-500">Date Filed:</span>
                                    {{ ticket.systemIssueReport.dateFiled ?? '—' }}
                                </div>
                                <div>
                                    <span class="text-slate-500">Requesting Employee:</span>
                                    {{ ticket.systemIssueReport.requestingEmployee ?? '—' }}
                                </div>
                                <div>
                                    <span class="text-slate-500">Employee Contact No.:</span>
                                    {{ ticket.systemIssueReport.employeeContactNo ?? '—' }}
                                </div>
                                <div>
                                    <span class="text-slate-500">Employee ID:</span>
                                    {{ ticket.systemIssueReport.employeeId ?? '—' }}
                                </div>
                                <div>
                                    <span class="text-slate-500">Signature of Employee:</span>
                                    {{ ticket.systemIssueReport.signatureOfEmployee ?? '—' }}
                                </div>
                                <div>
                                    <span class="text-slate-500">Nature of Appointment:</span>
                                    {{ ticket.systemIssueReport.natureOfAppointment ?? '—' }}
                                </div>
                                <div class="sm:col-span-2">
                                    <span class="text-slate-500">Name of Software:</span>
                                    {{ ticket.systemIssueReport.nameOfSoftware ?? '—' }}
                                </div>
                                <div class="sm:col-span-2">
                                    <span class="text-slate-500">Type of Request:</span>
                                    {{ Array.isArray(ticket.systemIssueReport.typeOfRequest) ? ticket.systemIssueReport.typeOfRequest.join(', ') : (ticket.systemIssueReport.typeOfRequest ?? '—') }}
                                </div>
                                <div class="sm:col-span-2">
                                    <span class="text-slate-500">Error Summary/Title:</span>
                                    {{ ticket.systemIssueReport.errorSummaryTitle ?? '—' }}
                                </div>
                                <div class="sm:col-span-2">
                                    <span class="text-slate-500">Detailed Description:</span>
                                    <p class="mt-1 whitespace-pre-wrap">{{ ticket.systemIssueReport.detailedDescription ?? '—' }}</p>
                                </div>
                            </div>
                            <div v-if="ticket.systemIssueReportAttachments?.length" class="mt-3 border-t border-slate-200 pt-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                    Screenshot / Attachments
                                </p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <a
                                        v-for="att in ticket.systemIssueReportAttachments"
                                        :key="att.name"
                                        :href="att.url ?? undefined"
                                        :target="att.url ? '_blank' : undefined"
                                        :rel="att.url ? 'noreferrer' : undefined"
                                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-blue-600 hover:bg-slate-50"
                                    >
                                        {{ att.name }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-center pt-2">
                            <Link
                                :href="dashboard().url"
                                class="rounded-full bg-blue-600 px-10 py-3 text-sm font-semibold uppercase tracking-[0.2em] text-white shadow-lg shadow-blue-600/40 transition hover:bg-blue-500"
                            >
                                Back to Dashboard
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
