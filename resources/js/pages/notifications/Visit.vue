<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';

type Props = {
    externalUrl: string;
    returnUrl: string;
    notificationId: string;
};

const props = defineProps<Props>();
const popupBlocked = ref(false);

const safeExternalUrl = computed(() => String(props.externalUrl || '').trim());
const safeReturnUrl = computed(() => String(props.returnUrl || '').trim() || '/dashboard');

onMounted(() => {
    try {
        const w = window.open(safeExternalUrl.value, '_blank', 'noopener,noreferrer');
        if (!w) {
            popupBlocked.value = true;
        }
    } catch {
        popupBlocked.value = true;
    } finally {
        window.location.assign(safeReturnUrl.value);
    }
});
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Notifications', href: '/notifications' },
        ]"
    >
        <div class="mx-auto w-full max-w-2xl space-y-6">
            <div class="rounded-2xl border bg-card p-6 text-card-foreground">
                <HeadingSmall
                    title="Opening feedback site…"
                    description="We’ll open the feedback website first, then return you to your ticket."
                />

                <div v-if="popupBlocked" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-200/15 dark:bg-amber-200/10 dark:text-amber-100">
                    Your browser blocked the new tab. Use the button below to open the feedback site.
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <a
                        :href="safeExternalUrl"
                        target="_blank"
                        rel="noreferrer noopener"
                    >
                        <Button type="button" variant="outline">
                            Open feedback site
                        </Button>
                    </a>

                    <Link :href="safeReturnUrl">
                        <Button type="button">
                            Go back to ticket
                        </Button>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

