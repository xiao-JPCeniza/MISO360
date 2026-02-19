<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    status: number;
    message?: string;
}>();

const defaultMessages: Record<number, string> = {
    401: 'Authentication required to access this page.',
    403: 'You do not have permission to access this page.',
    404: 'The page you are looking for could not be found.',
    419: 'Your session has expired. Please refresh the page and try again.',
    500: 'Something went wrong on our end. Please try again later.',
};

function messageFor(status: number, message?: string): string {
    if (message && message.trim().length > 0) {
        return message;
    }
    return defaultMessages[status] ?? 'An error occurred.';
}
</script>

<template>
    <Head :title="`Error ${status}`" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 dark:bg-slate-900">
        <div class="w-full max-w-md space-y-6 text-center">
            <div
                class="mx-auto flex h-32 w-32 items-center justify-center text-slate-300 dark:text-slate-600 sm:h-40 sm:w-40"
                aria-hidden="true"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="h-full w-full"
                >
                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" />
                    <line x1="2" y1="2" x2="22" y2="22" stroke-dasharray="2 2" />
                </svg>
            </div>
            <p class="text-6xl font-bold text-slate-300 dark:text-slate-600" aria-hidden="true">
                {{ status }}
            </p>
            <h1 class="text-xl font-semibold text-slate-800 dark:text-slate-200">
                {{ messageFor(status, message) }}
            </h1>
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                <Link
                    href="/"
                    class="inline-flex items-center justify-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600"
                >
                    Go to home
                </Link>
                <Link
                    href="/dashboard"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                >
                    Dashboard
                </Link>
            </div>
        </div>
    </div>
</template>
