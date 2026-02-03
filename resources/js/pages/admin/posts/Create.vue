<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ImageIcon, AlignLeft, AlignRight, Upload } from 'lucide-vue-next';
import { ref, computed } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Post Management', href: '/admin/posts' },
    { title: 'Add slide', href: '/admin/posts/create' },
];

const form = useForm({
    image: null as File | null,
    title: '',
    subtitle: '',
    text_position: 'left',
    sort_order: '' as string | number,
    is_active: true,
});

const previewUrl = ref<string | null>(null);
const isDragging = ref(false);

const imagePreviewUrl = computed(() => previewUrl.value);

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    setFile(file ?? null);
}

function setFile(file: File | null) {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = null;
    form.image = file;
    form.clearErrors('image');
    if (file) previewUrl.value = URL.createObjectURL(file);
}

function onDrop(e: DragEvent) {
    e.preventDefault();
    isDragging.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file && file.type.startsWith('image/')) setFile(file);
}

function onDragOver(e: DragEvent) {
    e.preventDefault();
    isDragging.value = true;
}

function onDragLeave() {
    isDragging.value = false;
}

function submit() {
    form.transform((data) => ({
        ...data,
        sort_order: data.sort_order === '' || data.sort_order === null ? null : Number(data.sort_order),
    })).post('/admin/posts', {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            setFile(null);
        },
    });
}
</script>

<template>
    <Head title="Add slide – Post Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-8 p-6">
            <section class="rounded-3xl border border-sidebar-border/50 bg-linear-to-br from-card to-card/80 p-6 shadow-lg shadow-black/5 dark:from-card dark:to-card/90 dark:shadow-black/20">
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Add slide</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Create a new profile slide for the homepage carousel. Drag and drop an image or use the button below.
                </p>

                <form class="mt-8 space-y-8" @submit.prevent="submit">
                    <!-- Drag-and-drop image -->
                    <div>
                        <label class="block text-sm font-semibold text-foreground">
                            Background image <span class="text-rose-500">*</span>
                        </label>
                        <div
                            class="relative mt-3 flex min-h-[200px] flex-col items-center justify-center rounded-2xl border-2 border-dashed transition-all duration-200"
                            :class="isDragging
                                ? 'border-primary bg-primary/10 dark:bg-primary/20'
                                : form.errors.image
                                    ? 'border-rose-300 bg-rose-50/50 dark:border-rose-500/50 dark:bg-rose-500/10'
                                    : 'border-sidebar-border/70 bg-muted/30 hover:border-primary/50 hover:bg-muted/50 dark:bg-muted/20'"
                            @dragover="onDragOver"
                            @dragleave="onDragLeave"
                            @drop="onDrop"
                        >
                            <template v-if="imagePreviewUrl">
                                <img
                                    :src="imagePreviewUrl"
                                    alt="Preview"
                                    class="max-h-64 w-full rounded-xl object-contain object-center shadow-inner"
                                />
                                <p class="mt-3 text-sm font-medium text-muted-foreground">
                                    Click or drag a new image to replace
                                </p>
                            </template>
                            <template v-else>
                                <Upload class="h-12 w-12 text-muted-foreground" />
                                <p class="mt-3 text-sm font-medium text-foreground">
                                    Drag and drop an image here, or click to browse
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    JPG, PNG or WebP · Max 5 MB
                                </p>
                            </template>
                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="absolute inset-0 cursor-pointer opacity-0"
                                @change="onFileChange"
                            />
                        </div>
                        <p v-if="form.errors.image" class="mt-2 text-sm text-rose-600">
                            {{ form.errors.image }}
                        </p>
                    </div>

                    <!-- Title & Subtitle -->
                    <div class="grid gap-6 sm:grid-cols-1">
                        <div>
                            <label class="block text-sm font-semibold text-foreground">
                                Title <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                maxlength="255"
                                placeholder="e.g. DRIVEN BY IMAGINATION"
                                class="mt-2 w-full rounded-xl border border-sidebar-border/70 bg-background px-4 py-3 text-sm transition-shadow focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            />
                            <p v-if="form.errors.title" class="mt-2 text-sm text-rose-600">
                                {{ form.errors.title }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-foreground">
                                Subtitle
                            </label>
                            <input
                                v-model="form.subtitle"
                                type="text"
                                maxlength="500"
                                placeholder="e.g. Power to do Good"
                                class="mt-2 w-full rounded-xl border border-sidebar-border/70 bg-background px-4 py-3 text-sm transition-shadow focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            />
                            <p v-if="form.errors.subtitle" class="mt-2 text-sm text-rose-600">
                                {{ form.errors.subtitle }}
                            </p>
                        </div>
                    </div>

                    <!-- Text position toggle -->
                    <div>
                        <label class="block text-sm font-semibold text-foreground">
                            Text position on slide <span class="text-rose-500">*</span>
                        </label>
                        <div class="mt-3 flex gap-3">
                            <button
                                type="button"
                                class="flex flex-1 items-center justify-center gap-2 rounded-xl border-2 py-3 transition-all"
                                :class="form.text_position === 'left'
                                    ? 'border-primary bg-primary/10 text-primary dark:bg-primary/20'
                                    : 'border-sidebar-border/70 bg-muted/30 text-muted-foreground hover:border-sidebar-border hover:bg-muted/50'"
                                @click="form.text_position = 'left'"
                            >
                                <AlignLeft class="h-5 w-5" />
                                Left
                            </button>
                            <button
                                type="button"
                                class="flex flex-1 items-center justify-center gap-2 rounded-xl border-2 py-3 transition-all"
                                :class="form.text_position === 'right'
                                    ? 'border-primary bg-primary/10 text-primary dark:bg-primary/20'
                                    : 'border-sidebar-border/70 bg-muted/30 text-muted-foreground hover:border-sidebar-border hover:bg-muted/50'"
                                @click="form.text_position = 'right'"
                            >
                                <AlignRight class="h-5 w-5" />
                                Right
                            </button>
                        </div>
                        <p v-if="form.errors.text_position" class="mt-2 text-sm text-rose-600">
                            {{ form.errors.text_position }}
                        </p>
                    </div>

                    <!-- Sort order -->
                    <div>
                        <label class="block text-sm font-semibold text-foreground">
                            Display order
                        </label>
                        <input
                            v-model.number="form.sort_order"
                            type="number"
                            min="0"
                            placeholder="0"
                            class="mt-2 w-full max-w-[140px] rounded-xl border border-sidebar-border/70 bg-background px-4 py-3 text-sm transition-shadow focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        />
                        <p v-if="form.errors.sort_order" class="mt-2 text-sm text-rose-600">
                            {{ form.errors.sort_order }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Lower numbers appear first in the carousel.
                        </p>
                    </div>

                    <!-- Active toggle -->
                    <div class="flex items-center gap-3 rounded-xl border border-sidebar-border/50 bg-muted/20 p-4">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            id="create-is-active"
                            class="h-4 w-4 rounded border-sidebar-border/70 text-primary focus:ring-primary"
                        />
                        <label for="create-is-active" class="text-sm font-medium text-foreground">
                            Active — show this slide on the profile section
                        </label>
                    </div>
                    <p v-if="form.errors.is_active" class="text-sm text-rose-600">
                        {{ form.errors.is_active }}
                    </p>

                    <!-- Actions -->
                    <div class="flex flex-wrap gap-3 border-t border-sidebar-border/50 pt-6">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-md shadow-primary/25 transition-all hover:shadow-lg hover:brightness-105 disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                            :disabled="form.processing"
                        >
                            <ImageIcon class="h-4 w-4" />
                            {{ form.processing ? 'Creating…' : 'Create slide' }}
                        </button>
                        <Link
                            href="/admin/posts"
                            class="inline-flex items-center rounded-xl border border-sidebar-border/70 px-5 py-2.5 text-sm font-semibold transition-colors hover:bg-muted/60 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>
