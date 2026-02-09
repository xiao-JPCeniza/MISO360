<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

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

    <div class="min-h-screen bg-slate-950 text-white">
        <div class="mx-auto flex min-h-screen w-full max-w-6xl items-center px-6">
            <div class="grid w-full gap-10 lg:grid-cols-[1fr_460px]">
                <div class="flex flex-col gap-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">
                        MISO 360
                    </p>
                    <h1 class="text-4xl font-semibold leading-tight">
                        Your devices and requests, centralized
                    </h1>
                    <p class="max-w-xl text-base text-slate-300">
                        Sign in to view device inventory, create and track requests, and
                        manage your day-to-day MISO tasks.
                    </p>
                
                </div>

                <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-8 shadow-2xl shadow-black/40">
                    <div class="flex gap-2 rounded-2xl bg-slate-950/60 p-1 text-sm font-semibold">
                        <button
                            type="button"
                            class="flex-1 rounded-2xl px-4 py-2 transition"
                            :class="
                                activePanel === 'signin'
                                    ? 'bg-sky-500 text-slate-900'
                                    : 'text-slate-300 hover:text-white'
                            "
                            @click="activePanel = 'signin'"
                        >
                            Sign in
                        </button>
                        <button
                            type="button"
                            class="flex-1 rounded-2xl px-4 py-2 transition"
                            :class="
                                activePanel === 'register'
                                    ? 'bg-sky-500 text-slate-900'
                                    : 'text-slate-300 hover:text-white'
                            "
                            @click="activePanel = 'register'"
                        >
                            Register
                        </button>
                    </div>

                    <form
                        v-if="activePanel === 'signin'"
                        class="mt-6 flex flex-col gap-5"
                        @submit.prevent="submitLogin"
                    >
                        <div>
                            <label
                                for="login-email"
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Email address
                            </label>
                            <input
                                id="login-email"
                                v-model="loginForm.email"
                                type="email"
                                autocomplete="username"
                                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30"
                                placeholder="you@example.com"
                            />
                            <p
                                v-if="loginForm.errors.email"
                                class="mt-2 text-sm text-rose-400"
                            >
                                {{ loginForm.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="login-password"
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Password
                            </label>
                            <input
                                id="login-password"
                                v-model="loginForm.password"
                                type="password"
                                autocomplete="current-password"
                                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30"
                                placeholder="••••••••"
                            />
                            <p
                                v-if="loginForm.errors.password"
                                class="mt-2 text-sm text-rose-400"
                            >
                                {{ loginForm.errors.password }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between text-sm text-slate-400">
                            <label class="flex items-center gap-2">
                                <input
                                    v-model="loginForm.remember"
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
                            :disabled="loginForm.processing"
                        >
                            Log in
                        </button>
                    </form>

                    <form
                        v-else
                        class="mt-6 flex flex-col gap-5"
                        @submit.prevent="submitRegister"
                    >
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label
                                    for="register-name"
                                    class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                                >
                                    Full name
                                </label>
                                <input
                                    id="register-name"
                                    v-model="registerForm.name"
                                    type="text"
                                    autocomplete="name"
                                    class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30"
                                    placeholder="Juan Dela Cruz"
                                />
                                <p
                                    v-if="registerForm.errors.name"
                                    class="mt-2 text-sm text-rose-400"
                                >
                                    {{ registerForm.errors.name }}
                                </p>
                            </div>
                            <div>
                                <label
                                    for="register-position"
                                    class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                                >
                                    Position/Designation
                                </label>
                                <input
                                    id="register-position"
                                    v-model="registerForm.position_title"
                                    type="text"
                                    autocomplete="organization-title"
                                    class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30"
                                    placeholder="Administrative Officer"
                                />
                                <p
                                    v-if="registerForm.errors.position_title"
                                    class="mt-2 text-sm text-rose-400"
                                >
                                    {{ registerForm.errors.position_title }}
                                </p>
                            </div>
                            <div>
                                <label
                                    for="register-office"
                                    class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                                >
                                    Office
                                </label>
                                <select
                                    id="register-office"
                                    v-model.number="registerForm.office_designation_id"
                                    class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30"
                                >
                                    <option :value="null" disabled>
                                        Select your office
                                    </option>
                                    <option v-for="office in (props.offices ?? [])" :key="office.id" :value="office.id">
                                        {{ office.name }}
                                    </option>
                                </select>
                                <p
                                    v-if="registerForm.errors.office_designation_id"
                                    class="mt-2 text-sm text-rose-400"
                                >
                                    {{ registerForm.errors.office_designation_id }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label
                                for="register-email"
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Email address
                            </label>
                            <input
                                id="register-email"
                                v-model="registerForm.email"
                                type="email"
                                autocomplete="email"
                                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30"
                                placeholder="you@example.com"
                            />
                            <div v-if="registerForm.email.length" class="mt-2 text-sm">
                                <span :class="emailValid ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ emailValid ? 'Email format looks good.' : 'Enter a valid email address.' }}
                                </span>
                            </div>
                            <p
                                v-if="registerForm.errors.email"
                                class="mt-2 text-sm text-rose-400"
                            >
                                {{ registerForm.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="register-password"
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Password
                            </label>
                            <input
                                id="register-password"
                                v-model="registerForm.password"
                                type="password"
                                autocomplete="new-password"
                                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30"
                                placeholder="Create a password"
                            />
                            <p
                                v-if="registerForm.errors.password"
                                class="mt-2 text-sm text-rose-400"
                            >
                                {{ registerForm.errors.password }}
                            </p>
                        </div>

                        <button
                            type="submit"
                            class="flex w-full items-center justify-center rounded-xl bg-sky-500 px-4 py-3 text-sm font-semibold text-slate-900 transition hover:bg-sky-400 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="registerForm.processing || !registerReady"
                        >
                            Create account
                        </button>
                        <p class="text-xs text-slate-400">
                            A one-time verification link will be sent to your email.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
