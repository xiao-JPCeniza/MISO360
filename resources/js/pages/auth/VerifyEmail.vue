<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

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

    <div class="min-h-screen bg-slate-950 text-white">
        <div class="mx-auto flex min-h-screen w-full max-w-4xl items-center px-6">
            <div class="w-full rounded-3xl border border-slate-800 bg-slate-900/70 p-10 shadow-2xl shadow-black/40">
                <div class="flex flex-col gap-6">
                    <div class="flex items-center gap-3 text-sm uppercase tracking-[0.2em] text-slate-400">
                        Email verification
                    </div>
                    <h1 class="text-3xl font-semibold leading-tight">
                        Verify your email to unlock the dashboard
                    </h1>
                    <p class="text-base text-slate-300">
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
                            class="rounded-xl bg-sky-500 px-5 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-sky-400 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="form.processing"
                            @click="resend"
                        >
                            Resend verification email
                        </button>
                        <Link href="/logout" method="post" as="button" class="text-sm text-slate-300">
                            Sign out
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
