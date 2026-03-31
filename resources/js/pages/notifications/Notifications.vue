<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { useNotifications } from '@/composables/useNotifications';

/** Customer satisfaction / feedback (opens in a new browser window). */
const FEEDBACK_URL = 'https://feedback.manolofortich.gov.ph/';

type NotificationItem = {
    id: string;
    readAt: string | null;
    createdAt: string | null;
    data: Record<string, unknown>;
};

const toast = useNotifications();
const items = ref<NotificationItem[]>([]);
const unreadCount = ref<number>(0);
const loading = ref(true);

function notificationReturnPath(n: NotificationItem): string {
    const raw = n.data.url;
    if (typeof raw !== 'string' || raw.trim() === '') {
        return '/dashboard';
    }

    const u = raw.trim();
    if (u.startsWith('/')) {
        return u;
    }

    try {
        const parsed = new URL(u);
        if (parsed.origin === window.location.origin) {
            return `${parsed.pathname}${parsed.search}${parsed.hash}`;
        }
    } catch {
        /* ignore invalid URL */
    }

    return '/dashboard';
}

function openNotification(n: NotificationItem): void {
    window.open(FEEDBACK_URL, '_blank', 'noopener,noreferrer');
    void fetch(`/notifications/${n.id}/mark-read`, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN':
                (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: '{}',
    });
    router.visit(notificationReturnPath(n));
}

async function load(): Promise<void> {
    loading.value = true;
    try {
        const res = await fetch('/notifications/data', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!res.ok) {
            throw new Error('Failed to load notifications.');
        }
        const payload = (await res.json()) as { unreadCount: number; items: NotificationItem[] };
        unreadCount.value = payload.unreadCount ?? 0;
        items.value = Array.isArray(payload.items) ? payload.items : [];
    } catch (e) {
        toast.error(e instanceof Error ? e.message : 'Failed to load notifications.');
    } finally {
        loading.value = false;
    }
}

async function markAllRead(): Promise<void> {
    try {
        const res = await fetch('/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
                    ?.content,
            },
            credentials: 'same-origin',
        });
        if (!res.ok) {
            throw new Error('Failed to mark notifications as read.');
        }
        await load();
        toast.success('All notifications marked as read.');
    } catch (e) {
        toast.error(e instanceof Error ? e.message : 'Failed to mark notifications as read.');
    }
}

onMounted(() => {
    void load();
});
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Notifications', href: '/notifications' },
        ]"
    >
        <div class="mx-auto w-full max-w-3xl space-y-6">
            <div class="flex items-center justify-between gap-4">
                <div class="space-y-1">
                    <HeadingSmall title="Notifications" description="New users and new ticket submissions." />
                    <p class="text-sm text-muted-foreground">
                        Unread: <span class="font-semibold text-foreground">{{ unreadCount }}</span>
                    </p>
                </div>
                <Button :disabled="unreadCount === 0 || loading" variant="outline" @click="markAllRead">
                    Mark all as read
                </Button>
            </div>

            <div class="rounded-2xl border bg-card p-4 text-card-foreground">
                <div v-if="loading" class="space-y-3">
                    <div class="h-4 w-2/3 animate-pulse rounded bg-muted"></div>
                    <div class="h-4 w-1/2 animate-pulse rounded bg-muted"></div>
                    <div class="h-4 w-3/4 animate-pulse rounded bg-muted"></div>
                </div>

                <div v-else-if="items.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                    No notifications yet.
                </div>

                <div v-else class="space-y-3">
                    <a
                        v-for="n in items"
                        :key="n.id"
                        href="#"
                        class="block cursor-pointer rounded-xl border border-border/70 p-4 transition-colors hover:bg-muted/50"
                        :class="[n.readAt ? 'opacity-85' : 'ring-1 ring-[#2563eb]/20']"
                        @click.prevent="openNotification(n)"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <p class="text-sm font-semibold">
                                    {{ String(n.data.title ?? 'Notification') }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ String(n.data.message ?? '') }}
                                </p>
                            </div>
                            <span
                                v-if="!n.readAt"
                                class="inline-flex rounded-full bg-[#2563eb]/10 px-2 py-1 text-xs font-semibold text-[#2563eb] dark:bg-white/10 dark:text-[#93c5fd]"
                            >
                                New
                            </span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

