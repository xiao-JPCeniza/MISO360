<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    email: string;
    purpose: string;
    status?: string;
}>();

const form = useForm({
    code: '',
});

const RESEND_THROTTLE_MS = 60000;
const SUBMIT_THROTTLE_MS = 3000;
let lastSubmitAt = 0;
let lastResendAt = 0;
const resendInProgress = ref(false);

const submit = () => {
    const now = Date.now();
    if (now - lastSubmitAt < SUBMIT_THROTTLE_MS || form.processing) return;
    lastSubmitAt = now;
    form.post('/two-factor/challenge');
};

const resend = () => {
    const now = Date.now();
    if (now - lastResendAt < RESEND_THROTTLE_MS || resendInProgress.value) return;
    lastResendAt = now;
    resendInProgress.value = true;
    router.post('/two-factor/resend', {}, {
        preserveScroll: true,
        onFinish: () => { resendInProgress.value = false; },
    });
};
</script>

<template>
    <Head title="Two-factor verification" />

    <div class="min-h-screen bg-background text-foreground">
        <div class="mx-auto flex min-h-screen w-full max-w-4xl items-center px-6">
            <div class="w-full rounded-3xl border border-border bg-card p-8 shadow-2xl shadow-black/10">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-muted-foreground">
                        Verify access
                    </p>
                    <h1 class="text-3xl font-semibold">
                        Check your email for a 6-digit code
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        We sent a one-time verification code to
                        <span class="font-semibold">{{ props.email }}</span>.
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Purpose: {{ props.purpose === 'login' ? 'Login' : 'Sensitive action' }}
                    </p>
                </div>

                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <div>
                        <label
                            for="code"
                            class="text-xs font-semibold uppercase tracking-wide text-muted-foreground"
                        >
                            Verification code
                        </label>
                        <input
                            id="code"
                            v-model="form.code"
                            type="text"
                            autocomplete="one-time-code"
                            maxlength="6"
                            class="mt-2 w-full rounded-xl border border-input bg-background px-4 py-3 text-sm text-foreground outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/30"
                            placeholder="123456"
                        />
                        <p v-if="form.errors.code" class="mt-2 text-sm text-rose-400">
                            {{ form.errors.code }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        class="flex w-full min-h-[44px] items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-primary-foreground transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing"
                            class="inline-block size-5 shrink-0 animate-spin rounded-full border-2 border-primary-foreground/30 border-t-primary-foreground"
                            aria-hidden="true"
                        />
                        <span>{{ form.processing ? 'Verifying…' : 'Verify' }}</span>
                    </button>
                </form>

                <div class="mt-6 flex flex-col gap-3 text-sm text-muted-foreground">
                    <button
                        type="button"
                        class="flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-border px-4 py-2 text-sm font-semibold transition hover:bg-muted disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="resendInProgress"
                        @click="resend"
                    >
                        <span v-if="resendInProgress"
                            class="inline-block size-4 shrink-0 animate-spin rounded-full border-2 border-current border-t-transparent"
                            aria-hidden="true"
                        />
                        <span>{{ resendInProgress ? 'Sending…' : 'Resend code' }}</span>
                    </button>
                    <p v-if="props.status" class="text-xs text-emerald-400">
                        {{ props.status }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
