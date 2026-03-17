<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

import type { ServiceTimerPayload } from '@/types/serviceTimer';

const props = defineProps<{
    serviceTimer: ServiceTimerPayload | null;
}>();

const displaySeconds = ref(0);
let intervalId: ReturnType<typeof setInterval> | null = null;

function formatDuration(totalSeconds: number): string {
    const h = Math.floor(totalSeconds / 3600);
    const m = Math.floor((totalSeconds % 3600) / 60);
    const s = totalSeconds % 60;
    return [h, m, s].map((n) => String(n).padStart(2, '0')).join(':');
}

function startTicking() {
    if (intervalId) return;
    intervalId = setInterval(() => {
        displaySeconds.value += 1;
    }, 1000);
}

function stopTicking() {
    if (intervalId) {
        clearInterval(intervalId);
        intervalId = null;
    }
}

const isInactive = computed(() => {
    const t = props.serviceTimer;
    if (!t) return true;
    const n = (t.statusName ?? '').toLowerCase();
    return ['pending', 'unassigned'].includes(n) || !t.statusName;
});

const statusHint = computed(() => {
    const t = props.serviceTimer;
    if (!t || isInactive.value) return 'Timer starts when status is set to Ongoing';
    if (t.isPaused) return 'Timer paused. Resume when status returns to Ongoing.';
    return 'Timer running (Philippine Standard Time)';
});

watch(
    () => props.serviceTimer,
    (t) => {
        stopTicking();
        if (!t) {
            displaySeconds.value = 0;
            return;
        }
        displaySeconds.value = t.elapsedSeconds;
        if (t.isActive && !t.isPaused) {
            startTicking();
        }
    },
    { immediate: true },
);

onMounted(() => {
    const t = props.serviceTimer;
    if (t?.isActive && !t.isPaused) {
        displaySeconds.value = t.elapsedSeconds;
        startTicking();
    }
});

onUnmounted(() => {
    stopTicking();
});
</script>

<template>
    <div
        class="rounded-xl border-2 px-4 py-3 shadow-lg transition-colors"
        :class="[
            isInactive
                ? 'border-amber-400/50 bg-amber-500/15 text-amber-900 dark:border-amber-400/40 dark:bg-amber-500/20 dark:text-amber-100'
                : serviceTimer?.isPaused
                  ? 'border-slate-400/50 bg-slate-500/15 text-slate-900 dark:border-slate-400/40 dark:bg-slate-500/20 dark:text-slate-100'
                  : 'border-emerald-500/60 bg-emerald-500/20 text-emerald-900 dark:border-emerald-400/50 dark:bg-emerald-500/25 dark:text-emerald-50',
        ]"
    >
        <p
            class="font-mono text-2xl font-bold tabular-nums tracking-tight sm:text-3xl"
            :class="[
                isInactive
                    ? 'text-amber-700 dark:text-amber-200'
                    : serviceTimer?.isPaused
                      ? 'text-slate-700 dark:text-slate-200'
                      : 'text-emerald-800 dark:text-emerald-100',
            ]"
        >
            {{ formatDuration(displaySeconds) }}
        </p>
        <p class="mt-1 text-[10px] opacity-80">
            {{ statusHint }}
        </p>
    </div>
</template>
