<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, Camera, ScanLine } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';

const videoRef = ref<HTMLVideoElement | null>(null);
const scanning = ref(false);
const scanError = ref('');
const manualCode = ref('');
const isResolving = ref(false);

const supportsScanner = computed(
    () => typeof window !== 'undefined' && 'BarcodeDetector' in window,
);

let detector: BarcodeDetector | null = null;
let stream: MediaStream | null = null;
let frameHandle = 0;
let lastScanAt = 0;

const normalizedCode = computed(() => manualCode.value.trim().toUpperCase());

function stopScanner() {
    scanning.value = false;
    if (frameHandle) {
        cancelAnimationFrame(frameHandle);
        frameHandle = 0;
    }
    if (stream) {
        stream.getTracks().forEach((track) => track.stop());
        stream = null;
    }
}

async function resolveCode(code: string) {
    if (!code || isResolving.value) {
        return;
    }

    isResolving.value = true;
    scanError.value = '';

    try {
        const response = await fetch(`/scan/lookup/${encodeURIComponent(code)}`, {
            headers: {
                Accept: 'application/json',
            },
        });

        const payload = (await response.json()) as {
            redirect?: string;
            message?: string;
        };

        if (!response.ok || !payload.redirect) {
            scanError.value =
                payload.message ||
                'Unable to locate a record for this QR code.';
            return;
        }

        router.visit(payload.redirect);
    } catch (error) {
        scanError.value =
            'We could not reach the server. Please try again.';
        console.error(error);
    } finally {
        isResolving.value = false;
    }
}

async function scanFrame() {
    if (!detector || !videoRef.value || !scanning.value || isResolving.value) {
        return;
    }

    const now = performance.now();
    if (now - lastScanAt < 400) {
        frameHandle = requestAnimationFrame(scanFrame);
        return;
    }
    lastScanAt = now;

    try {
        const codes = await detector.detect(videoRef.value);
        if (codes.length) {
            const value = codes[0]?.rawValue?.trim().toUpperCase();
            if (value) {
                stopScanner();
                await resolveCode(value);
                return;
            }
        }
    } catch (error) {
        console.error(error);
    }

    frameHandle = requestAnimationFrame(scanFrame);
}

async function startScanner() {
    if (!supportsScanner.value || scanning.value || isResolving.value) {
        return;
    }

    scanError.value = '';

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' },
        });

        if (!videoRef.value) {
            return;
        }

        videoRef.value.srcObject = stream;
        await videoRef.value.play();

        detector = new BarcodeDetector({ formats: ['qr_code'] });
        scanning.value = true;
        frameHandle = requestAnimationFrame(scanFrame);
    } catch (error) {
        scanError.value =
            'Camera access was blocked. Please allow camera permissions.';
        console.error(error);
        stopScanner();
    }
}

function submitManual() {
    resolveCode(normalizedCode.value);
}

onMounted(() => {
    if (supportsScanner.value) {
        startScanner();
    }
});

onBeforeUnmount(() => {
    stopScanner();
});
</script>

<template>
    <Head title="Scan QR Code" />

    <AppLayout>
        <div class="flex flex-1 flex-col gap-6 p-6">
            <section class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <p class="text-xs uppercase tracking-[0.3em] text-muted-foreground">
                            Scanner
                        </p>
                        <h1 class="text-2xl font-semibold">QR Code Scanner</h1>
                        <p class="text-sm text-muted-foreground">
                            Scan MIS asset QR codes to retrieve inventory records instantly.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 rounded-full border border-sidebar-border/70 px-4 py-2 text-sm font-semibold text-muted-foreground">
                        <ScanLine class="h-4 w-4" />
                        <span>{{ scanning ? 'Scanning live' : 'Scanner idle' }}</span>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-[1.4fr_0.9fr]">
                <div class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold">Live camera feed</h2>
                        <button
                            v-if="supportsScanner"
                            type="button"
                            class="rounded-full border border-sidebar-border/70 px-4 py-2 text-xs font-semibold text-muted-foreground transition-colors hover:bg-muted/40"
                            @click="scanning ? stopScanner() : startScanner()"
                        >
                            {{ scanning ? 'Pause' : 'Start' }}
                        </button>
                    </div>

                    <div
                        class="relative mt-4 overflow-hidden rounded-2xl border border-dashed border-sidebar-border/70 bg-muted/20"
                    >
                        <div class="aspect-video w-full bg-black/80">
                            <video
                                ref="videoRef"
                                class="h-full w-full object-cover"
                                muted
                                playsinline
                            ></video>
                        </div>
                        <div
                            v-if="!supportsScanner"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/60 p-6 text-center text-sm text-white"
                        >
                            <AlertTriangle class="h-6 w-6" />
                            <p>
                                Your browser does not support live QR scanning.
                                Use the manual entry box instead.
                            </p>
                        </div>
                        <div
                            v-else-if="!scanning"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/60 p-6 text-center text-sm text-white"
                        >
                            <Camera class="h-6 w-6" />
                            <p>Tap start to activate the scanner.</p>
                        </div>
                    </div>

                    <p class="mt-4 text-xs text-muted-foreground">
                        Align the QR code within the frame. The scanner will automatically load the linked record.
                    </p>
                </div>

                <div class="rounded-3xl border border-sidebar-border/60 bg-background p-6">
                    <h2 class="text-base font-semibold">Manual lookup</h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        If the camera cannot scan, enter the MIS UID manually.
                    </p>

                    <div class="mt-4 space-y-3">
                        <input
                            v-model="manualCode"
                            type="text"
                            placeholder="MIS-UID-00001"
                            class="w-full rounded-xl border border-sidebar-border/70 bg-background px-3 py-2 text-sm"
                        />
                        <button
                            type="button"
                            class="w-full rounded-xl bg-[#2563eb] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#1d4ed8] disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="!normalizedCode || isResolving"
                            @click="submitManual"
                        >
                            {{ isResolving ? 'Searching...' : 'Find record' }}
                        </button>
                        <p v-if="scanError" class="text-sm text-red-500">
                            {{ scanError }}
                        </p>
                    </div>

                    <div class="mt-6 rounded-xl bg-muted/40 px-4 py-3 text-xs text-muted-foreground">
                        <p class="font-semibold text-foreground">Tips</p>
                        <p class="mt-2">
                            Use the official MIS labels generated by the QR tool to ensure accurate results.
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
