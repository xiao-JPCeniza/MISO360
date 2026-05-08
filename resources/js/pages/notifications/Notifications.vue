<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { useNotifications } from '@/composables/useNotifications';
import AppLayout from '@/layouts/AppLayout.vue';

/** Customer satisfaction / feedback (opens in a new browser window). */
const FEEDBACK_URL = 'https://feedback.manolofortich.gov.ph/';

type NotificationItem = {
    id: string;
    readAt: string | null;
    createdAt: string | null;
    data: Record<string, unknown>;
};

type Pagination = {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from: number | null;
    to: number | null;
};

const toast = useNotifications();
const page = usePage();
const items = ref<NotificationItem[]>([]);
const unreadCount = ref<number>(0);
const loading = ref(true);
const pagination = ref<Pagination | null>(null);

/**
 * Prefer Inertia shared `csrf_token` — the root HTML meta tag is not updated on client-side
 * Inertia visits, so it can go stale and cause 419 responses on POST.
 */
function getCsrfToken(): string {
    const fromProps = (page.props as { csrf_token?: string }).csrf_token;
    if (fromProps) {
        return fromProps;
    }
    if (typeof document === 'undefined') {
        return '';
    }
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    if (meta?.content) {
        return meta.content;
    }
    const tokenMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (tokenMatch?.[1]) {
        return decodeURIComponent(tokenMatch[1]);
    }
    return '';
}

function buildJsonPostHeaders(): Record<string, string> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
    const csrf = getCsrfToken();
    if (csrf) {
        headers['X-CSRF-TOKEN'] = csrf;
        headers['X-XSRF-TOKEN'] = csrf;
    }
    return headers;
}

function formatNotificationCreatedAt(value: string | null): string {
    if (!value) {
        return '';
    }
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
        return '';
    }
    return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(parsed);
}

function notificationKind(n: NotificationItem): string | undefined {
    const k = n.data.kind;
    return typeof k === 'string' ? k : undefined;
}

function isTicketCompletedNotification(n: NotificationItem): boolean {
    return notificationKind(n) === 'ticket_completed';
}

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

function notificationActionPath(n: NotificationItem): string | null {
    const raw = n.data.actionUrl;
    if (typeof raw !== 'string' || raw.trim() === '') {
        return null;
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

    return null;
}

function openNotification(n: NotificationItem): void {
    if (isTicketCompletedNotification(n)) {
        window.open(FEEDBACK_URL, '_blank', 'noopener,noreferrer');
    }
    void fetch(`/notifications/${n.id}/mark-read`, {
        method: 'POST',
        headers: buildJsonPostHeaders(),
        credentials: 'same-origin',
        body: '{}',
    });
    router.visit(notificationReturnPath(n));
}

function openNotificationAction(n: NotificationItem): void {
    const actionPath = notificationActionPath(n);
    if (!actionPath) {
        openNotification(n);
        return;
    }

    void fetch(`/notifications/${n.id}/mark-read`, {
        method: 'POST',
        headers: buildJsonPostHeaders(),
        credentials: 'same-origin',
        body: '{}',
    });

    router.visit(actionPath);
}

async function load(pageNumber = 1): Promise<void> {
    loading.value = true;
    try {
        const res = await fetch(`/notifications/data?page=${pageNumber}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!res.ok) {
            throw new Error('Failed to load notifications.');
        }
        const payload = (await res.json()) as {
            unreadCount: number;
            items: NotificationItem[];
            pagination?: Pagination;
        };
        unreadCount.value = payload.unreadCount ?? 0;
        items.value = Array.isArray(payload.items) ? payload.items : [];
        pagination.value = payload.pagination ?? null;
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
            headers: buildJsonPostHeaders(),
            credentials: 'same-origin',
            body: '{}',
        });
        if (!res.ok) {
            if (res.status === 419) {
                throw new Error('Your session has expired. Please refresh the page and try again.');
            }
            throw new Error('Failed to mark notifications as read.');
        }
        await load(pagination.value?.currentPage ?? 1);
        toast.success('All notifications marked as read.');
    } catch (e) {
        toast.error(e instanceof Error ? e.message : 'Failed to mark notifications as read.');
    }
}

function canGoPrev(): boolean {
    return (pagination.value?.currentPage ?? 1) > 1 && !loading.value;
}

function canGoNext(): boolean {
    const p = pagination.value;
    if (!p) {
        return false;
    }
    return p.currentPage < p.lastPage && !loading.value;
}

function goPrev(): void {
    const current = pagination.value?.currentPage ?? 1;
    if (current <= 1) {
        return;
    }
    void load(current - 1);
}

function goNext(): void {
    const p = pagination.value;
    if (!p) {
        return;
    }
    if (p.currentPage >= p.lastPage) {
        return;
    }
    void load(p.currentPage + 1);
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
                        class="block cursor-pointer rounded-xl border p-4 transition-colors"
                        :class="[
                            n.readAt
                                ? 'border-border/70 hover:bg-muted/50'
                                : 'border-[#2563eb]/20 bg-[#2563eb]/4 ring-1 ring-[#2563eb]/20 hover:bg-[#2563eb]/6 dark:border-[#93c5fd]/20 dark:bg-[#93c5fd]/8 dark:ring-[#93c5fd]/20 dark:hover:bg-[#93c5fd]/10',
                        ]"
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
                                <p
                                    v-if="isTicketCompletedNotification(n)"
                                    class="mt-2 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm text-amber-950 dark:border-amber-400/25 dark:bg-amber-400/10 dark:text-amber-100"
                                >
                                    Please complete our customer satisfaction feedback — the form opens in a new tab when
                                    you open this notification.
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-2 text-right">
                                <button
                                    v-if="notificationActionPath(n)"
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-full border border-border/70 bg-background px-2.5 py-1 text-xs font-semibold text-muted-foreground transition hover:bg-muted/40"
                                    title="Open"
                                    @click.prevent.stop="openNotificationAction(n)"
                                >
                                    <Icon name="arrowRight" class="h-3.5 w-3.5" />
                                    Open
                                </button>
                                <time
                                    v-if="n.createdAt"
                                    :datetime="n.createdAt"
                                    class="text-[11px] leading-tight text-muted-foreground tabular-nums"
                                >
                                    {{ formatNotificationCreatedAt(n.createdAt) }}
                                </time>
                                <span
                                    v-if="!n.readAt"
                                    class="inline-flex rounded-full bg-[#2563eb]/10 px-2 py-1 text-xs font-semibold text-[#2563eb] dark:bg-white/10 dark:text-[#93c5fd]"
                                >
                                    New
                                </span>
                            </div>
                        </div>
                    </a>

                    <div v-if="pagination && pagination.lastPage > 1" class="flex items-center justify-between gap-4 pt-2">
                        <p class="text-sm text-muted-foreground">
                            <span class="font-semibold text-foreground">
                                {{ pagination.from ?? 0 }}–{{ pagination.to ?? 0 }}
                            </span>
                            of
                            <span class="font-semibold text-foreground">{{ pagination.total }}</span>
                        </p>
                        <div class="flex items-center gap-2">
                            <Button variant="outline" :disabled="!canGoPrev()" @click="goPrev">Previous</Button>
                            <p class="text-sm text-muted-foreground tabular-nums">
                                Page <span class="font-semibold text-foreground">{{ pagination.currentPage }}</span> /
                                <span class="font-semibold text-foreground">{{ pagination.lastPage }}</span>
                            </p>
                            <Button variant="outline" :disabled="!canGoNext()" @click="goNext">Next</Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

