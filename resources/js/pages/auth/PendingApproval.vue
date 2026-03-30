<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import FlashAlert from '@/components/FlashAlert.vue';
</script>

<template>
    <Head title="Pending approval" />

    <div class="min-h-screen bg-background text-foreground">
        <FlashAlert />
        <div class="mx-auto flex min-h-screen w-full max-w-4xl items-center px-6">
            <div class="w-full rounded-3xl border border-border bg-card p-10 shadow-2xl shadow-black/10">
                <div class="flex flex-col gap-6">
                    <div class="text-sm uppercase tracking-[0.2em] text-muted-foreground">
                        Account pending
                    </div>
                    <h1 class="text-3xl font-semibold leading-tight">Pending administrator approval</h1>
                    <p class="text-base text-muted-foreground">
                        Your email is verified, but an administrator must verify your account before you can use
                        the application. You will be able to sign in normally once your status shows as verified.
                    </p>
                    <p class="text-sm text-muted-foreground">
                        If you believe this is taking too long, contact your MISO administrator.
                    </p>
                    <div>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition hover:opacity-90"
                        >
                            Sign out
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
