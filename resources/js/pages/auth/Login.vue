<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login');
};
</script>

<template>
    <Head title="Log in" />

    <div class="min-h-screen bg-slate-950 text-white">
        <div class="mx-auto flex min-h-screen w-full max-w-6xl items-center px-6">
            <div class="grid w-full gap-10 lg:grid-cols-[1fr_420px]">
                <div class="space-y-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">
                        MSO 360 Access
                    </p>
                    <h1 class="text-4xl font-semibold leading-tight">
                        Welcome back to MSO 360
                    </h1>
                    <p class="max-w-xl text-base text-slate-300">
                        Sign in to manage requests, monitor service delivery, and
                        keep operations moving with clarity and confidence.
                    </p>
                    <div class="flex items-center gap-3 text-sm text-slate-400">
                        <span class="h-px w-12 bg-slate-700"></span>
                        Use your assigned credentials
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-8 shadow-2xl shadow-black/40">
                    <form class="space-y-5" @submit.prevent="submit">
                        <div>
                            <label
                                for="email"
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Email address
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="username"
                                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30"
                                placeholder="you@example.com"
                            />
                            <p v-if="form.errors.email" class="mt-2 text-sm text-rose-400">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="password"
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Password
                            </label>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                autocomplete="current-password"
                                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30"
                                placeholder="••••••••"
                            />
                            <p v-if="form.errors.password" class="mt-2 text-sm text-rose-400">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between text-sm text-slate-400">
                            <label class="flex items-center gap-2">
                                <input
                                    v-model="form.remember"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-600 bg-slate-950 text-sky-500 focus:ring-sky-500"
                                />
                                Remember me
                            </label>
                            <Link href="/" class="text-slate-300 transition hover:text-white">
                                Back to home
                            </Link>
                        </div>

                        <button
                            type="submit"
                            class="flex w-full items-center justify-center rounded-xl bg-sky-500 px-4 py-3 text-sm font-semibold text-slate-900 transition hover:bg-sky-400 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            Log in
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
