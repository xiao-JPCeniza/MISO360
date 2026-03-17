<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import FlashAlert from '@/components/FlashAlert.vue';

defineProps<{
    status: string | null;
}>();

const form = useForm({});

const resend = () => {
    form.post('/email/verification-notification');
};
</script>

<template>
    <Head title="Verify your email" />

    <div class="min-h-screen bg-background text-foreground">
        <FlashAlert />
        <div class="mx-auto flex min-h-screen w-full max-w-4xl items-center px-6">
            <div class="w-full rounded-3xl border border-border bg-card p-10 shadow-2xl shadow-black/10">
                <div class="flex flex-col gap-6">
                    <div class="flex items-center gap-3 text-sm uppercase tracking-[0.2em] text-muted-foreground">
                        Email verification
                    </div>
                    <h1 class="text-3xl font-semibold leading-tight">
                        Verify your email to unlock the dashboard
                    </h1>
                    <p class="text-base text-muted-foreground">
                        We have sent a one-time verification link to your email
                        address. Please confirm your account to continue.
                    </p>
                    <div
                        v-if="status === 'verification-link-sent'"
                        class="rounded-2xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300"
                    >
                        A fresh verification link has been sent to your inbox.
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="form.processing"
                            @click="resend"
                        >
                            Resend verification email
                        </button>
                        <Link href="/logout" method="post" as="button" class="text-sm text-muted-foreground">
                            Sign out
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
