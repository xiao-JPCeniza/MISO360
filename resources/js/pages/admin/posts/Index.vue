<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Pencil, Trash2, ImageIcon, Hash, Eye, EyeOff } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type SlideRow = {
    id: number;
    imageUrl: string | null;
    title: string;
    subtitle: string | null;
    textPosition: string;
    sortOrder: number;
    isActive: boolean;
    updatedAt: string | null;
};

const props = defineProps<{
    slides: SlideRow[];
    filters: { search: string; sort: string };
}>();

const page = usePage();
const statusMessage = computed(() => (page.props.flash?.status as string) ?? null);
const showSuccess = ref(!!page.props.flash?.status);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Post Management', href: '/admin/posts' },
];

const search = ref(props.filters.search);
const sort = ref(props.filters.sort);
const deleteConfirmSlide = ref<SlideRow | null>(null);

const slidesToShow = computed(() => props.slides);

function applyFilters() {
    router.get('/admin/posts', { search: search.value, sort: sort.value }, { preserveState: true });
}

function confirmDelete(slide: SlideRow) {
    deleteConfirmSlide.value = slide;
}

function cancelDelete() {
    deleteConfirmSlide.value = null;
}

function doDelete() {
    if (deleteConfirmSlide.value === null) return;
    const id = deleteConfirmSlide.value.id;
    router.delete(`/admin/posts/${id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteConfirmSlide.value = null;
        },
    });
}

function excerpt(text: string | null, max = 60) {
    if (!text) return '';
    return text.length <= max ? text : text.slice(0, max).trim() + '…';
}
</script>

<template>
    <Head title="Post Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-8 p-6">
            <!-- Header -->
            <section class="rounded-3xl border border-sidebar-border/50 bg-linear-to-br from-card to-card/80 p-6 shadow-lg shadow-black/5 dark:from-card dark:to-card/90 dark:shadow-black/20">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-primary/80">
                            Super Admin
                        </p>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                            Post Management
                        </h1>
                        <p class="max-w-lg text-sm text-muted-foreground">
                            Manage profile slides shown on the homepage. Create, reorder, and toggle visibility with a few clicks.
                        </p>
                    </div>
                    <Link
                        href="/admin/posts/create"
                        class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-md shadow-primary/25 transition-all duration-200 hover:shadow-lg hover:shadow-primary/30 hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    >
                        <ImageIcon class="h-4 w-4" />
                        Add slide
                    </Link>
                </div>

                <!-- Success message with subtle animation -->
                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="!!statusMessage && showSuccess"
                        class="mt-4 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-emerald-200"
                        role="status"
                    >
                        <span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500" />
                        {{ statusMessage }}
                    </div>
                </Transition>

                <!-- Filters -->
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <div class="relative flex-1 min-w-[200px]">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search by title or subtitle…"
                            class="w-full rounded-xl border border-sidebar-border/70 bg-background py-2.5 pl-4 pr-4 text-sm transition-shadow focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    <select
                        v-model="sort"
                        class="rounded-xl border border-sidebar-border/70 bg-background px-4 py-2.5 text-sm transition-shadow focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                        <option value="order">Sort by order</option>
                        <option value="latest">Latest updated</option>
                        <option value="active">Active first</option>
                    </select>
                    <button
                        type="button"
                        class="rounded-xl border border-sidebar-border/70 bg-muted/50 px-4 py-2.5 text-sm font-medium transition-all hover:bg-muted focus:outline-none focus:ring-2 focus:ring-primary/20"
                        @click="applyFilters"
                    >
                        Apply
                    </button>
                </div>
            </section>

            <!-- Cards grid -->
            <section class="space-y-4">
                <div
                    v-if="slidesToShow.length === 0"
                    class="rounded-2xl border-2 border-dashed border-sidebar-border/60 bg-muted/20 p-12 text-center"
                >
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-muted/60">
                        <ImageIcon class="h-7 w-7 text-muted-foreground" />
                    </div>
                    <p class="mt-4 font-medium text-foreground">No slides yet</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Add your first slide to show a carousel on the profile section.
                    </p>
                    <Link
                        href="/admin/posts/create"
                        class="mt-6 inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition-all hover:brightness-105"
                    >
                        <ImageIcon class="h-4 w-4" />
                        Add slide
                    </Link>
                </div>

                <div
                    v-else
                    class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="(slide, i) in slidesToShow"
                        :key="slide.id"
                        class="group relative flex flex-col overflow-hidden rounded-2xl border border-sidebar-border/50 bg-card shadow-md shadow-black/5 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-primary/5 dark:border-white/10 dark:shadow-black/15 dark:hover:shadow-primary/10"
                        :style="{ animationDelay: `${i * 50}ms` }"
                    >
                        <!-- Thumbnail -->
                        <div class="relative aspect-video w-full overflow-hidden bg-muted/40">
                            <img
                                v-if="slide.imageUrl"
                                :src="slide.imageUrl"
                                :alt="slide.title"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-muted-foreground"
                            >
                                <ImageIcon class="h-12 w-12 opacity-50" />
                            </div>
                            <!-- Overlay badges -->
                            <div class="absolute left-3 top-3 flex flex-wrap gap-2">
                                <span
                                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium backdrop-blur-sm"
                                    :class="slide.isActive
                                        ? 'bg-emerald-500/90 text-white dark:bg-emerald-500/80'
                                        : 'bg-rose-500/90 text-white dark:bg-rose-500/80'"
                                >
                                    <Eye v-if="slide.isActive" class="h-3 w-3" />
                                    <EyeOff v-else class="h-3 w-3" />
                                    {{ slide.isActive ? 'Active' : 'Inactive' }}
                                </span>
                                <span class="rounded-lg bg-black/50 px-2 py-1 text-xs font-medium text-white backdrop-blur-sm">
                                    {{ slide.textPosition }}
                                </span>
                            </div>
                            <span
                                class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-lg bg-black/50 text-xs font-bold text-white backdrop-blur-sm"
                                :title="`Order: ${slide.sortOrder}`"
                            >
                                {{ slide.sortOrder }}
                            </span>
                        </div>

                        <!-- Content -->
                        <div class="flex flex-1 flex-col gap-3 p-4">
                            <h3 class="line-clamp-2 font-semibold leading-snug text-foreground">
                                {{ slide.title }}
                            </h3>
                            <p
                                v-if="slide.subtitle"
                                class="line-clamp-2 text-sm text-muted-foreground"
                            >
                                {{ excerpt(slide.subtitle) }}
                            </p>
                            <div class="mt-auto flex items-center justify-between gap-2 border-t border-sidebar-border/50 pt-3">
                                <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <Hash class="h-3.5 w-3.5" />
                                    {{ slide.sortOrder }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <Link
                                        :href="`/admin/posts/${slide.id}/edit`"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-sidebar-border/60 bg-muted/40 text-muted-foreground transition-all hover:bg-primary hover:text-primary-foreground hover:border-primary focus:outline-none focus:ring-2 focus:ring-ring"
                                        title="Edit"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                    <button
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 text-rose-600 transition-all hover:bg-rose-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-rose-500/50 dark:border-rose-500/40"
                                        title="Delete"
                                        @click="confirmDelete(slide)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Delete confirmation modal -->
        <Dialog
            :open="deleteConfirmSlide !== null"
            @update:open="(open) => !open && cancelDelete()"
        >
            <DialogContent class="sm:max-w-md rounded-2xl border border-sidebar-border/60 shadow-2xl">
                <DialogHeader>
                    <DialogTitle id="delete-title" class="text-lg font-semibold text-foreground">
                        Delete this slide?
                    </DialogTitle>
                    <DialogDescription>
                        “{{ deleteConfirmSlide?.title }}” and its image will be removed. This can’t be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="flex justify-end gap-3 sm:justify-end">
                    <button
                        type="button"
                        class="rounded-xl border border-sidebar-border/70 px-4 py-2.5 text-sm font-medium transition-colors hover:bg-muted/60"
                        @click="cancelDelete"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
                        @click="doDelete"
                    >
                        Delete
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
