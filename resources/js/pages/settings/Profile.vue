<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/profile';
import type { BreadcrumbItem, User } from '@/types';
import { Camera, Check, X } from 'lucide-vue-next';

interface Props {
    status?: string;
    errors?: Record<string, string>;
    auth?: {
        user: User | null;
    };
}

withDefaults(defineProps<Props>(), {
    errors: () => ({}),
});

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage();
const user = computed(() => page.props.auth?.user as User | undefined);
const { getInitials } = useInitials();

const form = useForm({
    name: user.value?.name || '',
});

const ACCEPT = 'image/jpeg,image/jpg,image/png,image/webp';
const MAX_SIZE_MB = 5;
const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;

const AVATAR_INPUT_ID = 'profile-avatar-input';
const avatarFile = ref<File | null>(null);
const avatarPreviewUrl = ref<string | null>(null);
const avatarUploading = ref(false);
const avatarError = ref<string | null>(null);
const isDragOver = ref(false);

const currentAvatarUrl = computed(
    () =>
        (user.value?.avatar_url ?? (user.value?.avatar && user.value.avatar !== '' ? user.value.avatar : null)) as
            | string
            | null,
);

function validateAndSetFile(file: File): boolean {
    avatarError.value = null;
    if (!ACCEPT.split(',').some((m) => file.type === m.trim())) {
        avatarError.value = 'Please choose a JPG, PNG, or WEBP image.';
        return false;
    }
    if (file.size > MAX_SIZE_BYTES) {
        avatarError.value = `Image must be under ${MAX_SIZE_MB} MB.`;
        return false;
    }
    if (avatarPreviewUrl.value) URL.revokeObjectURL(avatarPreviewUrl.value);
    avatarPreviewUrl.value = URL.createObjectURL(file);
    avatarFile.value = file;
    return true;
}

function onAvatarFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;
    validateAndSetFile(file);
    target.value = '';
}

function onAvatarDrop(e: DragEvent) {
    e.preventDefault();
    isDragOver.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        avatarError.value = 'Please use a JPG, PNG, or WEBP image.';
        return;
    }
    validateAndSetFile(file);
}

function onAvatarDragOver(e: DragEvent) {
    e.preventDefault();
    isDragOver.value = true;
}

function onAvatarDragLeave() {
    isDragOver.value = false;
}

function clearAvatarSelection() {
    if (avatarPreviewUrl.value) URL.revokeObjectURL(avatarPreviewUrl.value);
    avatarPreviewUrl.value = null;
    avatarFile.value = null;
    avatarError.value = null;
}

/**
 * Upload avatar via POST FormData. Requires shared csrf_token (HandleInertiaRequests).
 * 419: ensure csrf_token is shared and _token is in FormData; refresh page if stale.
 * 422: check file type (JPG/PNG/WEBP) and size (max 5MB); errors show in displayAvatarError.
 * Image saved but UI not updating: router.reload() refreshes shared auth so avatar_url updates.
 */
function saveAvatar() {
    if (!avatarFile.value) return;
    avatarError.value = null;
    avatarUploading.value = true;

    const csrfToken = (page.props as { csrf_token?: string }).csrf_token ?? '';
    if (!csrfToken) {
        avatarUploading.value = false;
        avatarError.value = 'Session expired. Please refresh the page and try again.';
        return;
    }

    const formData = new FormData();
    formData.append('_token', csrfToken);
    formData.append('avatar', avatarFile.value);

    router.post('/settings/profile/avatar', formData, {
        forceFormData: true,
        preserveState: false,
        onFinish: () => {
            avatarUploading.value = false;
        },
        onSuccess: () => {
            // Optimistically keep showing the selected preview until the new auth user arrives.
            const keepPreview = avatarPreviewUrl.value;
            router.reload({
                preserveState: false,
                onFinish: () => {
                    // If the server response hasn’t populated a new avatar yet, fall back to the preview.
                    if (keepPreview && !currentAvatarUrl.value) {
                        avatarPreviewUrl.value = keepPreview;
                    }
                    clearAvatarSelection();
                },
            });
        },
        onError: (errors) => {
            avatarError.value = (errors?.avatar as string) || 'Failed to update photo.';
        },
    });
}

const avatarStatus = computed(() => page.props.status as string | undefined);
const avatarValidationError = computed(() => (page.props.errors as Record<string, string> | undefined)?.avatar);
const displayAvatarError = computed(
    () => avatarError.value || avatarValidationError.value,
);
const previewUrl = computed(() => avatarPreviewUrl.value ?? currentAvatarUrl.value);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <h1 class="sr-only">Profile Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-8">
                <section class="flex flex-col space-y-6">
                    <HeadingSmall
                        title="Profile picture"
                        description="Click your photo to change it. JPG, PNG or WEBP, 2–5 MB. Shown across the app."
                    />

                    <div class="flex flex-col items-center gap-6 sm:items-start">
                        <input
                            :id="AVATAR_INPUT_ID"
                            type="file"
                            :accept="ACCEPT"
                            class="sr-only"
                            aria-label="Choose profile photo"
                            @change="onAvatarFileChange"
                        />

                        <label
                            :for="AVATAR_INPUT_ID"
                            class="group relative flex shrink-0 cursor-pointer select-none rounded-full outline-none focus-within:ring-2 focus-within:ring-[#2563eb] focus-within:ring-offset-2 focus-within:ring-offset-background dark:focus-within:ring-[#93c5fd] dark:focus-within:ring-offset-[#0b1b3a]"
                            :class="[
                                isDragOver &&
                                    'ring-4 ring-[#2563eb]/60 ring-offset-2 ring-offset-background dark:ring-[#93c5fd]/60 dark:ring-offset-[#0b1b3a]',
                            ]"
                            @drop.prevent="onAvatarDrop"
                            @dragover.prevent="onAvatarDragOver"
                            @dragleave.prevent="onAvatarDragLeave"
                        >
                            <span class="sr-only">Change profile picture</span>
                            <Avatar
                                class="h-36 w-36 overflow-hidden rounded-full border-4 border-white shadow-lg dark:border-slate-800/80 sm:h-40 sm:w-40"
                                aria-hidden="true"
                            >
                                <AvatarImage
                                    v-if="previewUrl"
                                    :src="previewUrl"
                                    :alt="user?.name ?? 'Profile'"
                                    class="object-cover"
                                />
                                <AvatarFallback
                                    class="rounded-full bg-slate-200 text-4xl text-slate-600 dark:bg-slate-700 dark:text-slate-300 sm:text-5xl"
                                >
                                    {{ user ? getInitials(user.name) : '?' }}
                                </AvatarFallback>
                            </Avatar>
                            <span
                                class="absolute inset-0 flex flex-col items-center justify-center rounded-full bg-black/50 opacity-0 transition-opacity duration-200 group-hover:opacity-100 group-focus-within:opacity-100"
                                aria-hidden="true"
                            >
                                <Camera class="h-10 w-10 text-white sm:h-12 sm:w-12" />
                                <span class="mt-1.5 text-sm font-medium text-white">
                                    {{ avatarFile ? 'Choose different photo' : 'Change Photo' }}
                                </span>
                            </span>
                        </label>

                        <div v-if="avatarFile" class="flex flex-wrap items-center gap-2">
                            <Button
                                type="button"
                                :disabled="avatarUploading"
                                data-test="save-avatar-button"
                                class="gap-2"
                                @click="saveAvatar"
                            >
                                <Check class="h-4 w-4" aria-hidden="true" />
                                {{ avatarUploading ? 'Saving…' : 'Save' }}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="avatarUploading"
                                class="gap-2"
                                @click="clearAvatarSelection"
                            >
                                <X class="h-4 w-4" aria-hidden="true" />
                                Cancel
                            </Button>
                        </div>

                        <p
                            v-if="avatarStatus"
                            class="text-sm font-medium text-emerald-600 dark:text-emerald-400"
                            role="status"
                        >
                            {{ avatarStatus }}
                        </p>
                        <InputError :message="displayAvatarError ?? undefined" class="text-center sm:text-left" />
                    </div>
                </section>

                <section class="flex flex-col space-y-6">
                    <HeadingSmall
                        title="Profile information"
                        description="Update your name and email address"
                    />

                <form
                    @submit.prevent="form.patch('/settings/profile')"
                    class="space-y-6"
                >
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            name="name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user?.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                            disabled
                        />
                        <InputError class="mt-2" :message="(form.errors as Record<string, string>).email" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            data-test="update-profile-button"
                            >Save</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="form.recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </form>
                </section>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
