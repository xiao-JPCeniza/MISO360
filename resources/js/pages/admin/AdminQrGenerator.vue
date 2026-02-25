<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import QRCode from 'qrcode';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';

type QrItem = {
    id: string;
    dataUrl: string;
};

type StoredBatch = {
    id: string;
    start: number;
    end: number;
    createdAt: string;
    ids: string[];
    dataUrls: string[];
};

type ServerBatch = {
    id: number;
    start: number;
    end: number;
    createdAt: string;
    count: number;
};

const perPage = 20;
const storageKeys = {
    lastBatch: 'miso.qr.lastBatch',
    batchHistory: 'miso.qr.batchHistory',
};

const props = defineProps<{
    nextStart: number;
    totalGenerated: number;
}>();

const quantity = ref(20);
const startNumber = ref(props.nextStart ?? 1);
const isGenerating = ref(false);
const printRequired = ref(false);
const errorMessage = ref('');
const qrItems = ref<QrItem[]>([]);
const nextStart = ref(props.nextStart ?? 1);
const totalGenerated = ref(props.totalGenerated ?? 0);
const printArea = ref<HTMLElement | null>(null);
const lastBatchAvailable = ref(false);
const batchHistory = ref<StoredBatch[]>([]);
const serverBatches = ref<ServerBatch[]>([]);
const loadingServerBatches = ref(false);
const loadingBatchIds = ref(false);
const isRecallOpen = ref(false);

function handleAfterPrint() {
    printRequired.value = false;
}

onMounted(() => {
    if (typeof window === 'undefined') {
        return;
    }

    const storedBatch = window.localStorage.getItem(storageKeys.lastBatch);
    const storedHistory = window.localStorage.getItem(storageKeys.batchHistory);

    startNumber.value = nextStart.value;
    batchHistory.value = storedHistory ? (JSON.parse(storedHistory) as StoredBatch[]) : [];
    lastBatchAvailable.value = Boolean(storedBatch || batchHistory.value.length);

    window.addEventListener('afterprint', handleAfterPrint);
});

onUnmounted(() => {
    if (typeof window === 'undefined') {
        return;
    }

    window.removeEventListener('afterprint', handleAfterPrint);
});

const rangeLabel = computed(() => {
    const total = Math.max(1, Math.min(quantity.value, 500));
    const start = nextStart.value;
    const end = start + total - 1;

    return `${formatId(start)} - ${formatId(end)}`;
});

const pages = computed(() => {
    const result: QrItem[][] = [];

    for (let i = 0; i < qrItems.value.length; i += perPage) {
        result.push(qrItems.value.slice(i, i + perPage));
    }

    return result;
});

const totalPages = computed(() => pages.value.length);

function formatId(value: number) {
    return `MIS-UID-${value.toString().padStart(5, '0')}`;
}

function batchNumber(start: number) {
    return Math.floor((start - 1) / perPage) + 1;
}

function emptySlots(page: QrItem[]) {
    return Math.max(perPage - page.length, 0);
}

function getCsrfToken() {
    if (typeof document === 'undefined') {
        return '';
    }

    const meta = document.querySelector('meta[name="csrf-token"]') as
        | HTMLMetaElement
        | null;

    if (meta?.content) {
        return meta.content;
    }

    const tokenMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (tokenMatch && tokenMatch[1]) {
        return decodeURIComponent(tokenMatch[1]);
    }

    return '';
}

async function generateBatch() {
    errorMessage.value = '';
    if (printRequired.value) {
        errorMessage.value = 'Please print the current batch before generating a new one.';
        return;
    }

    isGenerating.value = true;

    const total = Math.max(1, Math.min(quantity.value, 500));
    const start = nextStart.value;

    try {
        quantity.value = total;
        startNumber.value = start;

        const csrfToken = getCsrfToken();
        const headers: Record<string, string> = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
            headers['X-XSRF-TOKEN'] = csrfToken;
        }

        const body = {
            quantity: total,
            ...(csrfToken ? { _token: csrfToken } : {}),
        };

        const response = await fetch('/admin/qr-generator/batch', {
            method: 'POST',
            headers,
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });

        const contentType = response.headers.get('content-type') ?? '';
        if (!contentType.includes('application/json')) {
            errorMessage.value =
                'Unable to generate QR codes. Please refresh and try again.';
            return;
        }

        const payload = (await response.json()) as {
            ids?: string[];
            nextStart?: number;
            totalGenerated?: number;
            message?: string;
            errors?: Record<string, string[]>;
        };

        if (!response.ok || !payload.ids) {
            const validationMessage = payload.errors
                ? Object.values(payload.errors).flat()[0]
                : undefined;
            errorMessage.value =
                validationMessage ||
                payload.message ||
                'Unable to generate QR codes. Please try again.';
            return;
        }

        const ids = payload.ids;

        const dataUrls = await Promise.all(
            ids.map((id) =>
                QRCode.toDataURL(id, {
                    errorCorrectionLevel: 'M',
                    margin: 0,
                    width: 240,
                }),
            ),
        );

        qrItems.value = ids.map((id, index) => ({
            id,
            dataUrl: dataUrls[index],
        }));

        nextStart.value = payload.nextStart ?? start + total;
        totalGenerated.value = payload.totalGenerated ?? totalGenerated.value + total;
        startNumber.value = nextStart.value;
        printRequired.value = true;

        if (typeof window !== 'undefined') {
            const batchRecord: StoredBatch = {
                id: `${start}-${start + total - 1}-${Date.now()}`,
                start,
                end: start + total - 1,
                createdAt: new Date().toISOString(),
                ids,
                dataUrls,
            };
            const updatedHistory = [batchRecord, ...batchHistory.value].slice(0, 100);
            batchHistory.value = updatedHistory;

            window.localStorage.setItem(
                storageKeys.lastBatch,
                JSON.stringify({ ids, dataUrls }),
            );
            window.localStorage.setItem(
                storageKeys.batchHistory,
                JSON.stringify(updatedHistory),
            );
            lastBatchAvailable.value = true;
        }
    } catch (error) {
        errorMessage.value = 'Unable to generate QR codes. Please try again.';
        qrItems.value = [];
        console.error(error);
    } finally {
        isGenerating.value = false;
    }
}

function printBatch() {
    if (!qrItems.value.length) {
        return;
    }
    window.print();
}

async function openRecallModal() {
    isRecallOpen.value = true;
    serverBatches.value = [];
    loadingServerBatches.value = true;
    try {
        const res = await fetch('/admin/qr-generator/batches', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (res.ok) {
            const data = (await res.json()) as { batches: ServerBatch[] };
            serverBatches.value = data.batches ?? [];
        }
    } catch {
        // keep serverBatches empty
    } finally {
        loadingServerBatches.value = false;
    }
}

async function loadServerBatch(batch: ServerBatch) {
    loadingBatchIds.value = true;
    errorMessage.value = '';
    try {
        const res = await fetch(`/admin/qr-generator/batches/${batch.id}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) {
            errorMessage.value = 'Could not load batch. Please try again.';
            return;
        }
        const data = (await res.json()) as { ids: string[] };
        const ids = data.ids ?? [];
        const dataUrls = await Promise.all(
            ids.map((id) =>
                QRCode.toDataURL(id, {
                    errorCorrectionLevel: 'M',
                    margin: 0,
                    width: 240,
                }),
            ),
        );
        qrItems.value = ids.map((id, index) => ({
            id,
            dataUrl: dataUrls[index],
        }));
        printRequired.value = true;
        isRecallOpen.value = false;
    } catch {
        errorMessage.value = 'Could not load batch. Please try again.';
    } finally {
        loadingBatchIds.value = false;
    }
}

async function downloadPdf() {
    if (!qrItems.value.length) {
        return;
    }

    const { jsPDF } = await import('jspdf');
    const doc = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait' });
    const pageW = 210;
    const pageH = 297;
    const cols = 4;
    const rows = 5;
    const perSheet = cols * rows;
    const cellW = pageW / cols;
    const cellH = pageH / rows;
    const labelH = 8;
    const qrAreaH = cellH - labelH;
    const imgSize = Math.min(cellW, qrAreaH) * 0.78;

    const greyRgb = 200;
    doc.setDrawColor(greyRgb, greyRgb, greyRgb);
    doc.setLineWidth(0.2);
    doc.setTextColor(0, 0, 0);

    const allItems = qrItems.value;
    const totalPages = Math.ceil(allItems.length / perSheet);

    for (let pageIndex = 0; pageIndex < totalPages; pageIndex++) {
        if (pageIndex > 0) {
            doc.addPage();
        }

        const pageItems = allItems.slice(
            pageIndex * perSheet,
            pageIndex * perSheet + perSheet,
        );

        for (let cr = 0; cr < rows; cr++) {
            for (let cc = 0; cc < cols; cc++) {
                doc.rect(cc * cellW, cr * cellH, cellW, cellH);
            }
        }

        for (let j = 0; j < pageItems.length; j++) {
            const item = pageItems[j];
            const col = j % cols;
            const row = Math.floor(j / cols);
            const cellX = col * cellW;
            const cellY = row * cellH;
            const qrX = cellX + (cellW - imgSize) / 2;
            const qrY = cellY + (qrAreaH - imgSize) / 2;
            doc.addImage(item.dataUrl, 'PNG', qrX, qrY, imgSize, imgSize);
            doc.setFontSize(7);
            doc.text(item.id, cellX + cellW / 2, cellY + qrAreaH + labelH / 2, {
                align: 'center',
                maxWidth: cellW - 2,
            });
        }
    }

    doc.save('miso-qr-batch.pdf');
}
</script>

<template>
    <Head title="QR Code Generator" />

    <AppLayout>
        <div class="qr-print-root flex flex-1 flex-col gap-6">
            <div
                class="flex flex-col gap-4 rounded-2xl border border-sidebar-border/60 bg-background p-6 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                        Admin Tools
                    </p>
                    <h1 class="mt-2 text-2xl font-semibold">
                        QR Code Generator
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Create print-ready batches of MIS QR labels with unique IDs.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link
                        href="/admin/dashboard"
                        class="rounded-full border border-border bg-background px-4 py-2 text-sm font-semibold text-muted-foreground transition-colors hover:bg-muted/50 hover:text-foreground dark:border-white/20 dark:hover:bg-white/10"
                    >
                        Back to Admin Dashboard
                    </Link>
                    <button
                        type="button"
                        class="rounded-full bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="!qrItems.length"
                        @click="printBatch"
                    >
                        Print A4 Batch
                    </button>
                    <button
                        type="button"
                        class="rounded-full border border-primary/50 bg-background px-4 py-2 text-sm font-semibold text-primary transition-colors hover:bg-primary/10 disabled:cursor-not-allowed disabled:opacity-60 dark:border-primary/40 dark:hover:bg-primary/20"
                        :disabled="!qrItems.length"
                        @click="downloadPdf"
                    >
                        Download PDF
                    </button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
                <section
                    class="qr-controls rounded-2xl border border-sidebar-border/60 bg-background p-6"
                >
                    <h2 class="text-lg font-semibold">Batch settings</h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Generate up to 500 QR codes at once. Each page prints 20 labels.
                    </p>

                    <div class="mt-6 space-y-5">
                        <div>
                            <label class="text-sm font-medium">Quantity</label>
                            <input
                                v-model.number="quantity"
                                type="number"
                                min="1"
                                max="500"
                                class="mt-2 w-full rounded-xl border border-sidebar-border/70 bg-background px-3 py-2 text-sm"
                            />
                        </div>

                        <div>
                            <label class="text-sm font-medium">Starting number</label>
                            <input
                                type="number"
                                :value="startNumber"
                                :min="nextStart"
                                disabled
                                class="mt-2 w-full rounded-xl border border-sidebar-border/70 bg-background px-3 py-2 text-sm"
                            />
                            <p class="mt-2 text-xs text-muted-foreground">
                                Starting number is based on the last issued UID and cannot be edited.
                            </p>
                            <p class="mt-2 text-xs text-muted-foreground">
                                Next range: <span class="font-medium">{{ rangeLabel }}</span>
                            </p>
                        </div>

                        <button
                            type="button"
                            class="w-full rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="isGenerating || printRequired"
                            @click="generateBatch"
                        >
                            {{ isGenerating ? 'Generating...' : 'Generate batch' }}
                        </button>
                        <button
                            type="button"
                            class="w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted/50 dark:border-white/20 dark:hover:bg-white/10"
                            @click="openRecallModal"
                        >
                            View previous batches
                        </button>

                        <p
                            v-if="printRequired"
                            class="text-sm text-amber-600 dark:text-amber-400"
                        >
                            Print the current batch before generating a new one.
                        </p>

                        <p
                            v-if="errorMessage"
                            class="text-sm text-red-500 dark:text-red-400"
                        >
                            {{ errorMessage }}
                        </p>

                        <div class="rounded-xl bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                            <p>
                                Total pages: <span class="font-semibold">{{ totalPages }}</span>
                            </p>
                            <p>
                                Labels ready:
                                <span class="font-semibold">{{ qrItems.length }}</span>
                            </p>
                            <p>
                                Next unique ID:
                                <span class="font-semibold">{{ formatId(nextStart) }}</span>
                            </p>
                            <p>
                                Total generated:
                                <span class="font-semibold">{{ totalGenerated }}</span>
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    class="qr-preview rounded-2xl border border-sidebar-border/60 bg-background p-6"
                >
                    <h2 class="text-lg font-semibold">Preview</h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Review the grid layout before printing. Pages are A4 with 4x5 labels.
                    </p>

                    <div v-if="!qrItems.length" class="mt-8 rounded-xl border border-dashed border-sidebar-border/70 p-10 text-center text-sm text-muted-foreground">
                        Generate a batch to see the print preview.
                    </div>

                    <div v-else ref="printArea" class="qr-print-area mt-6 space-y-10">
                        <div
                            v-for="(page, pageIndex) in pages"
                            :key="`page-${pageIndex}`"
                            class="qr-print-page rounded-2xl border border-dashed border-sidebar-border/60 p-6"
                        >
                            <div class="qr-grid">
                                <div
                                    v-for="item in page"
                                    :key="item.id"
                                    class="qr-card"
                                >
                                    <img
                                        :src="item.dataUrl"
                                        :alt="item.id"
                                        class="qr-image"
                                    />
                                    <p class="qr-label">
                                        {{ item.id }}
                                    </p>
                                </div>
                                <div
                                    v-for="slot in emptySlots(page)"
                                    :key="`empty-${pageIndex}-${slot}`"
                                    class="qr-card qr-card--empty"
                                ></div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <Dialog :open="isRecallOpen" @update:open="isRecallOpen = $event">
                <DialogContent class="sm:max-w-lg">
                    <DialogHeader class="space-y-3">
                        <DialogTitle>Previous QR code batches</DialogTitle>
                        <DialogDescription>
                            Load a batch from the database to reprint or download. Batches are available on any device.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="mt-4 max-h-[400px] overflow-y-auto">
                        <p
                            v-if="loadingServerBatches"
                            class="text-sm text-muted-foreground"
                        >
                            Loading…
                        </p>
                        <p
                            v-else-if="!serverBatches.length"
                            class="text-sm text-muted-foreground"
                        >
                            No batches in the database yet. Generate a batch to create one.
                        </p>
                        <div v-else class="space-y-2">
                            <button
                                v-for="batch in serverBatches"
                                :key="'server-' + batch.id"
                                type="button"
                                class="flex w-full items-center justify-between rounded-lg border border-border bg-background px-3 py-2 text-left text-sm font-semibold text-foreground transition-colors hover:bg-muted/50 disabled:opacity-60 dark:border-white/20 dark:hover:bg-white/10"
                                :disabled="loadingBatchIds"
                                @click="loadServerBatch(batch)"
                            >
                                <span>
                                    {{ formatId(batch.start) }} – {{ formatId(batch.end) }}
                                    ({{ batch.count }} labels)
                                </span>
                                <span class="text-xs text-muted-foreground">{{
                                    new Date(batch.createdAt).toLocaleDateString()
                                }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <DialogClose
                            class="rounded-full border border-border bg-background px-4 py-2 text-sm font-semibold text-muted-foreground transition-colors hover:bg-muted/50 hover:text-foreground dark:border-white/20 dark:hover:bg-white/10"
                        >
                            Close
                        </DialogClose>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style>
.qr-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    grid-template-rows: repeat(5, auto);
    gap: 8px;
    margin-top: 16px;
}

.qr-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    border-radius: 10px;
    background: #ffffff;
    padding: 8px 6px;
    box-shadow: 0 0 0 1px rgba(148, 163, 184, 0.3);
}

.qr-card--empty {
    background: transparent;
    box-shadow: none;
}

.qr-image {
    width: 100%;
    max-width: 110px;
    height: auto;
    image-rendering: pixelated;
}

.qr-label {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #0f172a;
}

@media print {
    @page {
        size: A4 portrait;
        margin: 0;
    }

    html,
    body {
        margin: 0 !important;
        padding: 0 !important;
        height: 297mm !important;
        max-height: 297mm !important;
        overflow: hidden !important;
        background: #ffffff;
        color: #000000;
    }

    body * {
        visibility: hidden;
    }

    .qr-print-area,
    .qr-print-area .qr-print-page:first-child,
    .qr-print-area .qr-print-page:first-child * {
        visibility: visible;
    }

    .qr-print-area {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 210mm !important;
        height: 297mm !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff;
        overflow: hidden;
    }

    .qr-print-area .qr-print-page:not(:first-child) {
        display: none !important;
    }

    header,
    nav,
    ol,
    .qr-controls,
    .qr-preview > h2,
    .qr-preview > p,
    .qr-print-root > div:first-child,
    .qr-preview .mt-8 {
        display: none !important;
    }

    main {
        max-width: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .qr-preview {
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .qr-print-page {
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .qr-print-page:not(:first-child) {
        display: none !important;
    }

    .qr-grid {
        gap: 0;
        grid-template-rows: repeat(5, minmax(0, 1fr));
        width: 210mm;
        height: 297mm;
        margin: 0;
        min-height: 0;
        border-right: 0.25mm solid #000000;
        border-bottom: 0.25mm solid #000000;
    }

    .qr-card {
        display: grid;
        grid-template-rows: 1fr minmax(4mm, auto);
        justify-items: center;
        align-items: center;
        padding: 0;
        box-shadow: none;
        border-left: 0.25mm solid #000000;
        border-top: 0.25mm solid #000000;
        break-inside: avoid;
        box-sizing: border-box;
        background: #ffffff;
        border-radius: 0;
        min-height: 0;
    }

    .qr-card--empty {
        border-left: 0.25mm solid #000000;
        border-top: 0.25mm solid #000000;
        background: #ffffff;
        border-radius: 0;
    }

    .qr-card .qr-image {
        width: 100%;
        max-height: 100%;
        min-height: 0;
        object-fit: contain;
    }

    .qr-card .qr-label {
        font-size: 6pt;
        letter-spacing: 0.06em;
        line-height: 1.1;
        min-height: 4mm;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
}

</style>
