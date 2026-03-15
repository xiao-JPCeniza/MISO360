<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

interface OfficeOption {
    id: number;
    name: string;
}

const props = defineProps<{
    offices: OfficeOption[];
}>();

const activePanel = ref<'signin' | 'register'>('signin');

const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

const registerForm = useForm({
    name: '',
    position_title: '',
    office_designation_id: null as number | null,
    email: '',
    password: '',
});

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const emailValid = computed(() => emailPattern.test(registerForm.email));
const registerReady = computed(() => {
    return (
        registerForm.name.trim().length > 0 &&
        registerForm.position_title.trim().length > 0 &&
        registerForm.office_designation_id !== null &&
        emailValid.value &&
        registerForm.password.length > 0
    );
});

const submitLogin = () => {
    loginForm.post('/login');
};

const submitRegister = () => {
    registerForm.post('/register');
};

onMounted(() => {
    if (Object.keys(registerForm.errors).length > 0) {
        activePanel.value = 'register';
    }
});
</script>

<template>

    <Head title="Sign in or register" />

    <div class="relative min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-white">
        <div class="absolute right-4 top-4 z-10 sm:right-6 sm:top-6">
            <ThemeSwitcher />
        </div>
        <div class="mx-auto flex min-h-screen w-full max-w-6xl items-center px-4 py-8 sm:px-6">
            <div class="grid w-full gap-8 lg:grid-cols-[1fr_460px] lg:gap-10">
                <div class="flex flex-col gap-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">
                        MISO 360 Access
                    </p>
                    <h1 class="text-3xl font-semibold leading-tight sm:text-4xl">
                        MISO360 Request Management System
                    </h1>
                    <p class="max-w-xl text-base text-slate-600 dark:text-slate-300">
                        A centralized platform for submitting, monitoring, and managing service requests within the
                        MISO360 system. Authorized users may sign in to access the dashboard, while new users may
                        register through a guided process to verify their office affiliation.
                    </p>
                    <div class="flex items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
                        <span class="h-px w-12 bg-slate-300 dark:bg-slate-700"></span>
                        “Your request drives our daily commitment to better service.”
                    </div>
                </div>

                <div
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900/70 dark:shadow-2xl dark:shadow-black/40 sm:p-8">
                    <div class="flex gap-2 rounded-2xl bg-slate-100 p-1 text-sm font-semibold dark:bg-slate-950/60">
                        <button type="button" class="flex-1 rounded-2xl px-4 py-2.5 transition sm:py-2" :class="activePanel === 'signin'
                                ? 'bg-sky-500 text-slate-900'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white'
                            " @click="activePanel = 'signin'">
                            Sign in
                        </button>
                        <button type="button" class="flex-1 rounded-2xl px-4 py-2.5 transition sm:py-2" :class="activePanel === 'register'
                                ? 'bg-sky-500 text-slate-900'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white'
                            " @click="activePanel = 'register'">
                            Register
                        </button>
                    </div>

                    <form v-if="activePanel === 'signin'" class="mt-6 flex flex-col gap-5"
                        @submit.prevent="submitLogin">
                        <div>
                            <label for="login-email"
                                class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                Email address
                            </label>
                            <input id="login-email" v-model="loginForm.email" type="email" autocomplete="username"
                                class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500"
                                placeholder="you@example.com" />
                            <p v-if="loginForm.errors.email" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
                                {{ loginForm.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label for="login-password"
                                class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                Password
                            </label>
                            <input id="login-password" v-model="loginForm.password" type="password"
                                autocomplete="current-password"
                                class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500"
                                placeholder="••••••••" />
                            <p v-if="loginForm.errors.password" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
                                {{ loginForm.errors.password }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between text-sm text-slate-500 dark:text-slate-400">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input v-model="loginForm.remember" type="checkbox"
                                    class="h-4 w-4 rounded border-slate-400 text-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-950" />
                                Remember me
                            </label>
                            <Link href="/"
                                class="text-slate-600 transition hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">
                                Back to home
                            </Link>
                        </div>

                        <button type="submit"
                            class="flex w-full min-h-[44px] items-center justify-center rounded-xl bg-sky-500 px-4 py-3 text-sm font-semibold text-slate-900 transition hover:bg-sky-400 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="loginForm.processing">
                            Log in
                        </button>
                    </form>

                    <form v-else class="mt-6 flex flex-col gap-5" @submit.prevent="submitRegister">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="register-name"
                                    class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    Full name
                                </label>
                                <input id="register-name" v-model="registerForm.name" type="text" autocomplete="name"
                                    class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500"
                                    placeholder="Juan Dela Cruz" />
                                <p v-if="registerForm.errors.name"
                                    class="mt-2 text-sm text-rose-600 dark:text-rose-400">
                                    {{ registerForm.errors.name }}
                                </p>
                            </div>
                            <div>
                                <label for="register-position"
                                    class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    Position/Designation
                                </label>
                                <input id="register-position" v-model="registerForm.position_title" type="text"
                                    autocomplete="organization-title"
                                    class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500"
                                    placeholder="Administrative Officer" />
                                <p v-if="registerForm.errors.position_title"
                                    class="mt-2 text-sm text-rose-600 dark:text-rose-400">
                                    {{ registerForm.errors.position_title }}
                                </p>
                            </div>
                            <div>
                                <label for="register-office"
                                    class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    Office
                                </label>
                                <select id="register-office" v-model.number="registerForm.office_designation_id"
                                    class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                    <option :value="null" disabled>
                                        Select your office
                                    </option>
                                    <option v-for="office in (props.offices ?? [])" :key="office.id" :value="office.id">
                                        {{ office.name }}
                                    </option>
                                </select>
                                <p v-if="registerForm.errors.office_designation_id"
                                    class="mt-2 text-sm text-rose-600 dark:text-rose-400">
                                    {{ registerForm.errors.office_designation_id }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label for="register-email"
                                class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                Email address
                            </label>
                            <input id="register-email" v-model="registerForm.email" type="email" autocomplete="email"
                                class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500"
                                placeholder="you@example.com" />
                            <div v-if="registerForm.email.length" class="mt-2 text-sm">
                                <span
                                    :class="emailValid ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                    {{ emailValid ? 'Email format looks good.' : 'Enter a valid email address.' }}
                                </span>
                            </div>
                            <p v-if="registerForm.errors.email" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
                                {{ registerForm.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label for="register-password"
                                class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                Password
                            </label>
                            <input id="register-password" v-model="registerForm.password" type="password"
                                autocomplete="new-password"
                                class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500"
                                placeholder="Create a password" />
                            <p v-if="registerForm.errors.password"
                                class="mt-2 text-sm text-rose-600 dark:text-rose-400">
                                {{ registerForm.errors.password }}
                            </p>
                        </div>

                        <button type="submit"
                            class="flex w-full min-h-[44px] items-center justify-center rounded-xl bg-sky-500 px-4 py-3 text-sm font-semibold text-slate-900 transition hover:bg-sky-400 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="registerForm.processing || !registerReady">
                            Create account
                        </button>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            A one-time verification link will be sent to your email.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
