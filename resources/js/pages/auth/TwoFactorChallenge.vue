<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    email: string;
    purpose: string;
    status?: string;
}>();

const form = useForm({
    code: '',
});

const submit = () => {
    form.post('/two-factor/challenge');
};

const resend = () => {
    router.post('/two-factor/resend');
};
</script>

<template>
    <Head title="Two-factor verification" />

    <div class="min-h-screen bg-slate-950 text-white">
        <div class="mx-auto flex min-h-screen w-full max-w-4xl items-center px-6">
            <div class="w-full rounded-3xl border border-slate-800 bg-slate-900/70 p-8 shadow-2xl shadow-black/40">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">
                        Verify access
                    </p>
                    <h1 class="text-3xl font-semibold">
                        Check your email for a 6-digit code
                    </h1>
                    <p class="text-sm text-slate-300">
                        We sent a one-time verification code to
                        <span class="font-semibold">{{ props.email }}</span>.
                    </p>
                    <p class="text-xs text-slate-400">
                        Purpose: {{ props.purpose === 'login' ? 'Login' : 'Sensitive action' }}
                    </p>
                </div>

                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <div>
                        <label
                            for="code"
                            class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Verification code
                        </label>
                        <input
                            id="code"
                            v-model="form.code"
                            type="text"
                            autocomplete="one-time-code"
                            maxlength="6"
                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30"
                            placeholder="123456"
                        />
                        <p v-if="form.errors.code" class="mt-2 text-sm text-rose-400">
                            {{ form.errors.code }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        class="flex w-full items-center justify-center rounded-xl bg-sky-500 px-4 py-3 text-sm font-semibold text-slate-900 transition hover:bg-sky-400 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        Verify
                    </button>
                </form>

                <div class="mt-6 flex flex-col gap-3 text-sm text-slate-300">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-700 px-4 py-2 text-sm font-semibold transition hover:bg-slate-800"
                        @click="resend"
                    >
                        Resend code
                    </button>
                    <p v-if="props.status" class="text-xs text-emerald-400">
                        {{ props.status }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
