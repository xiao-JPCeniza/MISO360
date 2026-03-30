<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AlertCircle, AlertTriangle, CheckCircle2, Info, Sparkles } from 'lucide-vue-next';

import HeadingSmall from '@/components/HeadingSmall.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Notification test',
        href: '/dev/notifications-test',
    },
];

const triggers = [
    {
        type: 'success',
        label: 'Success',
        description: 'Flash key: success',
        hint: 'Bouncy slide-in + success icon animation',
        orbClass: 'nt-mini nt-mini--success',
        rowAnimClass: 'nt-row-icon nt-row-icon--success',
    },
    {
        type: 'error',
        label: 'Error',
        description: 'Flash key: error',
        hint: 'Shake + wobble icon',
        orbClass: 'nt-mini nt-mini--error',
        rowAnimClass: 'nt-row-icon nt-row-icon--error',
    },
    {
        type: 'warning',
        label: 'Warning',
        description: 'Flash key: warning',
        hint: 'Tilt and settle',
        orbClass: 'nt-mini nt-mini--warning',
        rowAnimClass: 'nt-row-icon nt-row-icon--warning',
    },
    {
        type: 'info',
        label: 'Info',
        description: 'Flash key: info',
        hint: 'Drop-in from above',
        orbClass: 'nt-mini nt-mini--info',
        rowAnimClass: 'nt-row-icon nt-row-icon--info',
    },
    {
        type: 'status',
        label: 'Status (success style)',
        description: 'Flash key: status',
        hint: 'Same toast treatment as success',
        orbClass: 'nt-mini nt-mini--status',
        rowAnimClass: 'nt-row-icon nt-row-icon--status',
    },
] as const;
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Notification test" />

        <div class="nt-scene relative mx-auto max-w-2xl">
            <div class="nt-backdrop pointer-events-none absolute inset-0 z-0 overflow-hidden rounded-2xl" aria-hidden="true">
                <div class="nt-orb nt-orb--a nt-motion" />
                <div class="nt-orb nt-orb--b nt-motion" />
                <div class="nt-orb nt-orb--c nt-motion" />
                <div class="nt-orb nt-orb--d nt-motion" />

                <div class="nt-ring nt-motion" />
                <div class="nt-diamond nt-motion" />

                <div class="nt-float-icon nt-float-icon--1 nt-motion">
                    <CheckCircle2 class="size-7 text-emerald-500/80 dark:text-emerald-400/90" stroke-width="1.75" />
                </div>
                <div class="nt-float-icon nt-float-icon--2 nt-motion">
                    <AlertCircle class="size-6 text-red-500/75 dark:text-red-400/85" stroke-width="1.75" />
                </div>
                <div class="nt-float-icon nt-float-icon--3 nt-motion">
                    <Info class="size-6 text-sky-500/75 dark:text-sky-400/85" stroke-width="1.75" />
                </div>
                <div class="nt-float-icon nt-float-icon--4 nt-motion">
                    <Sparkles class="size-5 text-amber-500/70 dark:text-amber-400/80" stroke-width="1.75" />
                </div>

                <div
                    v-for="(t, i) in triggers"
                    :key="`mini-${t.type}`"
                    class="nt-mini-orb nt-motion"
                    :class="t.orbClass"
                    :style="{ '--nt-delay': `${i * 0.35}s` }"
                />
            </div>

            <div class="relative z-10 space-y-8 px-1 py-2 sm:px-2">
                <div class="relative">
                    <div class="nt-heading-glow nt-motion pointer-events-none absolute -inset-4 rounded-2xl blur-2xl" aria-hidden="true" />
                    <HeadingSmall
                        title="Notification test"
                        description="Local and testing only. Follow a link to fire a session flash and see the toast."
                    />
                </div>

                <div
                    class="relative rounded-lg border border-amber-500/40 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 backdrop-blur-sm dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-50"
                    role="note"
                >
                    <span
                        class="nt-badge nt-motion absolute -right-1 -top-1 flex size-8 items-center justify-center rounded-full bg-amber-400/90 text-amber-950 shadow-md dark:bg-amber-500/90 dark:text-amber-950"
                        aria-hidden="true"
                    >
                        <Sparkles class="size-4" />
                    </span>
                    This page is registered only when <code class="rounded bg-black/5 px-1 py-0.5 dark:bg-white/10">APP_ENV</code> is
                    <code class="rounded bg-black/5 px-1 py-0.5 dark:bg-white/10">local</code> or
                    <code class="rounded bg-black/5 px-1 py-0.5 dark:bg-white/10">testing</code>. It is not available in production.
                </div>

                <ul class="flex flex-col gap-3">
                    <li v-for="item in triggers" :key="item.type">
                        <Link
                            :href="`/dev/notifications-test/flash/${item.type}`"
                            class="group nt-card relative flex gap-4 overflow-hidden rounded-lg border border-border bg-card/95 p-4 text-left shadow-sm backdrop-blur-sm transition hover:border-primary/40 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <span
                                class="relative flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-border/80 bg-muted/50 shadow-inner"
                                :class="item.rowAnimClass"
                                aria-hidden="true"
                            >
                                <span class="nt-ping nt-motion absolute inline-flex size-9 rounded-xl opacity-40" />
                                <span class="relative z-1 flex size-8 items-center justify-center rounded-lg bg-background/80">
                                    <CheckCircle2 v-if="item.type === 'success' || item.type === 'status'" class="size-5 text-emerald-600 dark:text-emerald-400" />
                                    <AlertCircle v-else-if="item.type === 'error'" class="size-5 text-red-600 dark:text-red-400" />
                                    <AlertTriangle v-else-if="item.type === 'warning'" class="size-5 text-amber-600 dark:text-amber-400" />
                                    <Info v-else class="size-5 text-sky-600 dark:text-sky-400" />
                                </span>
                            </span>
                            <span class="flex min-w-0 flex-1 flex-col gap-1">
                                <span class="font-medium text-foreground">{{ item.label }}</span>
                                <span class="text-sm text-muted-foreground">{{ item.description }}</span>
                                <span class="text-xs text-muted-foreground">{{ item.hint }}</span>
                            </span>
                            <span
                                class="nt-chevron nt-motion text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-primary"
                                aria-hidden="true"
                            >
                                →
                            </span>
                        </Link>
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* —— backdrop orbs —— */
.nt-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(48px);
    opacity: 0.45;
}

.dark .nt-orb {
    opacity: 0.35;
}

.nt-orb--a {
    left: -12%;
    top: 8%;
    width: 14rem;
    height: 14rem;
    background: radial-gradient(circle, hsl(221 83% 60% / 0.55) 0%, transparent 70%);
    animation: nt-drift-a 18s ease-in-out infinite;
}

.nt-orb--b {
    right: -10%;
    top: 22%;
    width: 12rem;
    height: 12rem;
    background: radial-gradient(circle, hsl(160 55% 45% / 0.4) 0%, transparent 70%);
    animation: nt-drift-b 22s ease-in-out infinite;
}

.nt-orb--c {
    left: 20%;
    bottom: -5%;
    width: 11rem;
    height: 11rem;
    background: radial-gradient(circle, hsl(280 50% 55% / 0.35) 0%, transparent 70%);
    animation: nt-drift-c 20s ease-in-out infinite;
}

.nt-orb--d {
    right: 15%;
    bottom: 12%;
    width: 9rem;
    height: 9rem;
    background: radial-gradient(circle, hsl(35 90% 55% / 0.35) 0%, transparent 70%);
    animation: nt-drift-d 16s ease-in-out infinite;
}

/* —— small orbs tied to trigger types —— */
.nt-mini-orb {
    position: absolute;
    width: 0.65rem;
    height: 0.65rem;
    border-radius: 50%;
    opacity: 0.65;
    animation: nt-mini-orbit 14s linear infinite;
    animation-delay: var(--nt-delay, 0s);
}

.nt-mini {
    position: absolute;
    width: 0.65rem;
    height: 0.65rem;
    border-radius: 50%;
    box-shadow: 0 0 12px color-mix(in srgb, currentColor 35%, transparent);
}

.nt-mini--success {
    left: 8%;
    top: 42%;
    background: hsl(152 65% 42%);
    color: hsl(152 65% 42%);
}

.nt-mini--error {
    right: 6%;
    top: 38%;
    background: hsl(0 72% 52%);
    color: hsl(0 72% 52%);
}

.nt-mini--warning {
    left: 4%;
    bottom: 28%;
    background: hsl(38 92% 48%);
    color: hsl(38 92% 48%);
}

.nt-mini--info {
    right: 10%;
    bottom: 22%;
    background: hsl(199 85% 48%);
    color: hsl(199 85% 48%);
}

.nt-mini--status {
    left: 50%;
    top: 12%;
    margin-left: -0.325rem;
    background: hsl(152 60% 46%);
    color: hsl(152 60% 46%);
}

/* —— geometric accents —— */
.nt-ring {
    position: absolute;
    left: 50%;
    top: 0;
    margin-left: -5rem;
    width: 10rem;
    height: 10rem;
    border-radius: 50%;
    border: 2px dashed hsl(221 83% 53% / 0.25);
    animation: nt-spin-slow 48s linear infinite;
}

.nt-diamond {
    position: absolute;
    right: 8%;
    bottom: 18%;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.35rem;
    border: 2px solid hsl(215 20% 50% / 0.25);
    transform: rotate(45deg);
    animation: nt-diamond-pulse 5s ease-in-out infinite;
}

/* —— floating lucide wrappers —— */
.nt-float-icon {
    position: absolute;
    opacity: 0.55;
}

.nt-float-icon--1 {
    left: 6%;
    top: 18%;
    animation: nt-bob 5s ease-in-out infinite;
}

.nt-float-icon--2 {
    right: 5%;
    top: 48%;
    animation: nt-bob-alt 6.5s ease-in-out infinite 0.5s;
}

.nt-float-icon--3 {
    left: 12%;
    bottom: 14%;
    animation: nt-bob 7s ease-in-out infinite 1s;
}

.nt-float-icon--4 {
    right: 18%;
    top: 8%;
    animation: nt-sparkle 4s ease-in-out infinite;
}

/* —— heading ambient glow —— */
.nt-heading-glow {
    background: radial-gradient(ellipse at center, hsl(221 83% 53% / 0.12) 0%, transparent 65%);
    animation: nt-glow-breathe 8s ease-in-out infinite;
}

/* —— badge on notice —— */
.nt-badge {
    animation: nt-badge-bounce 3s ease-in-out infinite;
}

/* —— row icon containers —— */
.nt-row-icon--success .nt-ping {
    background: hsl(152 65% 45% / 0.25);
    animation: nt-ping-soft 2.8s ease-out infinite;
}

.nt-row-icon--error .nt-ping {
    background: hsl(0 72% 52% / 0.22);
    animation: nt-shake-box 2.5s ease-in-out infinite;
}

.nt-row-icon--warning .nt-ping {
    background: hsl(38 92% 50% / 0.22);
    animation: nt-tilt-box 3.2s ease-in-out infinite;
}

.nt-row-icon--info .nt-ping {
    background: hsl(199 85% 48% / 0.22);
    animation: nt-float-pulse 3s ease-in-out infinite;
}

.nt-row-icon--status .nt-ping {
    background: hsl(152 60% 46% / 0.22);
    animation: nt-ping-soft 2.6s ease-out infinite 0.4s;
}

.nt-row-icon--success {
    animation: nt-icon-pop 2.4s ease-in-out infinite;
}

.nt-row-icon--error {
    animation: nt-icon-shake 2.8s ease-in-out infinite;
}

.nt-row-icon--warning {
    animation: nt-icon-wobble 3s ease-in-out infinite;
}

.nt-row-icon--info {
    animation: nt-icon-drop 2.6s ease-in-out infinite;
}

.nt-row-icon--status {
    animation: nt-icon-pop 2.8s ease-in-out infinite 0.2s;
}

.nt-chevron {
    display: flex;
    align-items: center;
    padding-right: 0.25rem;
    font-size: 1.25rem;
    font-weight: 300;
    animation: nt-chevron-nudge 2.2s ease-in-out infinite;
}

.nt-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
        105deg,
        transparent 40%,
        hsl(221 83% 53% / 0.04) 50%,
        transparent 60%
    );
    transform: translateX(-100%);
    animation: nt-shimmer 6s ease-in-out infinite;
    pointer-events: none;
}

/* —— keyframes —— */
@keyframes nt-drift-a {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }
    50% {
        transform: translate(8%, 12%) scale(1.08);
    }
}

@keyframes nt-drift-b {
    0%,
    100% {
        transform: translate(0, 0);
    }
    50% {
        transform: translate(-10%, 8%);
    }
}

@keyframes nt-drift-c {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }
    50% {
        transform: translate(6%, -10%) scale(1.06);
    }
}

@keyframes nt-drift-d {
    0%,
    100% {
        transform: translate(0, 0);
    }
    33% {
        transform: translate(-6%, 4%);
    }
    66% {
        transform: translate(4%, -6%);
    }
}

@keyframes nt-mini-orbit {
    0% {
        transform: rotate(0deg) translateX(2.5rem) rotate(0deg);
    }
    100% {
        transform: rotate(360deg) translateX(2.5rem) rotate(-360deg);
    }
}

@keyframes nt-spin-slow {
    to {
        transform: rotate(360deg);
    }
}

@keyframes nt-diamond-pulse {
    0%,
    100% {
        opacity: 0.4;
        transform: rotate(45deg) scale(1);
    }
    50% {
        opacity: 0.85;
        transform: rotate(45deg) scale(1.08);
    }
}

@keyframes nt-bob {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

@keyframes nt-bob-alt {
    0%,
    100% {
        transform: translateY(0) rotate(-6deg);
    }
    50% {
        transform: translateY(-12px) rotate(6deg);
    }
}

@keyframes nt-sparkle {
    0%,
    100% {
        transform: scale(1) rotate(0deg);
        opacity: 0.45;
    }
    50% {
        transform: scale(1.15) rotate(15deg);
        opacity: 0.85;
    }
}

@keyframes nt-glow-breathe {
    0%,
    100% {
        opacity: 0.6;
        transform: scale(1);
    }
    50% {
        opacity: 1;
        transform: scale(1.03);
    }
}

@keyframes nt-badge-bounce {
    0%,
    100% {
        transform: translate(0, 0) rotate(-8deg);
    }
    50% {
        transform: translate(2px, -3px) rotate(8deg);
    }
}

@keyframes nt-ping-soft {
    0% {
        transform: scale(0.92);
        opacity: 0.5;
    }
    70% {
        transform: scale(1.15);
        opacity: 0;
    }
    100% {
        opacity: 0;
    }
}

@keyframes nt-shake-box {
    0%,
    100% {
        transform: translateX(0);
    }
    20% {
        transform: translateX(-3px);
    }
    40% {
        transform: translateX(3px);
    }
    60% {
        transform: translateX(-2px);
    }
    80% {
        transform: translateX(2px);
    }
}

@keyframes nt-tilt-box {
    0%,
    100% {
        transform: rotate(0deg);
    }
    25% {
        transform: rotate(-3deg);
    }
    75% {
        transform: rotate(3deg);
    }
}

@keyframes nt-float-pulse {
    0%,
    100% {
        transform: translateY(0);
        opacity: 0.35;
    }
    50% {
        transform: translateY(-4px);
        opacity: 0.55;
    }
}

@keyframes nt-icon-pop {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.04);
    }
}

@keyframes nt-icon-shake {
    0%,
    100% {
        transform: translateX(0);
    }
    25% {
        transform: translateX(-2px);
    }
    75% {
        transform: translateX(2px);
    }
}

@keyframes nt-icon-wobble {
    0%,
    100% {
        transform: rotate(0deg);
    }
    33% {
        transform: rotate(-2.5deg);
    }
    66% {
        transform: rotate(2.5deg);
    }
}

@keyframes nt-icon-drop {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-3px);
    }
}

@keyframes nt-chevron-nudge {
    0%,
    100% {
        transform: translateX(0);
    }
    50% {
        transform: translateX(3px);
    }
}

@keyframes nt-shimmer {
    0% {
        transform: translateX(-100%);
    }
    40%,
    100% {
        transform: translateX(100%);
    }
}

@media (prefers-reduced-motion: reduce) {
    .nt-motion,
    .nt-row-icon--success,
    .nt-row-icon--error,
    .nt-row-icon--warning,
    .nt-row-icon--info,
    .nt-row-icon--status,
    .nt-chevron,
    .nt-card::before {
        animation: none !important;
    }

    .nt-mini-orb {
        animation: none !important;
    }
}
</style>
