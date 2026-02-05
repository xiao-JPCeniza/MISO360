<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
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
const ACCEPT_TYPES = ACCEPT.split(',').map((type) => type.trim());
const MAX_SIZE_BYTES = 50 * 1024 * 1024;
const AVATAR_INPUT_ID = 'profile-avatar-input';
const avatarUploadUrl = '/settings/profile/avatar';

const currentAvatarUrl = computed(
    () =>
        (user.value?.avatar_url ?? (user.value?.avatar && user.value.avatar !== '' ? user.value.avatar : null)) as
            | string
            | null,
);
const csrfToken = computed(() => (page.props as { csrf_token?: string }).csrf_token ?? '');
const avatarStatus = computed(() => page.props.status as string | undefined);
const avatarValidationError = computed(() => (page.props.errors as Record<string, string> | undefined)?.avatar);
const userInitials = computed(() => (user.value ? getInitials(user.value.name) : '?'));

type ProfileAvatarUploaderOptions = {
    currentAvatar: string | null;
    csrfToken: string;
    uploadUrl: string;
    initials: string;
};

declare global {
    interface Window {
        profileAvatarUploader: (options: ProfileAvatarUploaderOptions) => Record<string, unknown>;
    }
}

window.profileAvatarUploader = (options: ProfileAvatarUploaderOptions) => {
    const initialAvatar = options.currentAvatar;

    return {
        file: null as File | null,
        previewUrl: options.currentAvatar,
        error: '',
        success: '',
        uploading: false,
        progress: 0,
        dragOver: false,
        initials: options.initials || '?',
        cameraLabel() {
            return this.file ? 'Choose different photo' : 'Change Photo';
        },
        choose() {
            const input = this.$refs.fileInput as HTMLInputElement | undefined;
            input?.click();
        },
        onDragOver(event: DragEvent) {
            event.preventDefault();
            this.dragOver = true;
        },
        onDragLeave() {
            this.dragOver = false;
        },
        onDrop(event: DragEvent) {
            event.preventDefault();
            this.dragOver = false;
            const file = event.dataTransfer?.files?.[0];
            if (file) {
                this.setFile(file);
            }
        },
        onChange(event: Event) {
            const target = event.target as HTMLInputElement;
            const file = target.files?.[0];
            if (file) {
                this.setFile(file);
            }
            target.value = '';
        },
        clearMessages() {
            this.error = '';
            this.success = '';
        },
        clearSelection() {
            if (this.previewUrl && this.previewUrl !== initialAvatar) {
                URL.revokeObjectURL(this.previewUrl);
            }
            this.previewUrl = initialAvatar;
            this.file = null;
            this.progress = 0;
            this.clearMessages();
        },
        setFile(file: File) {
            this.clearMessages();
            if (!ACCEPT_TYPES.includes(file.type)) {
                this.error = 'Please choose a JPG, PNG, or WEBP image.';
                return;
            }
            if (file.size > MAX_SIZE_BYTES) {
                this.error = 'Image must be under 50 MB.';
                return;
            }
            if (this.previewUrl && this.previewUrl !== initialAvatar) {
                URL.revokeObjectURL(this.previewUrl);
            }
            this.previewUrl = URL.createObjectURL(file);
            this.file = file;
        },
        save() {
            if (!this.file) {
                this.error = 'Please choose a photo first.';
                return;
            }
            if (!options.csrfToken) {
                this.error = 'Session expired. Please refresh and try again.';
                return;
            }

            this.uploading = true;
            this.error = '';
            this.success = '';
            this.progress = 0;

            const formData = new FormData();
            formData.append('_token', options.csrfToken);
            formData.append('avatar', this.file);

            router.post(options.uploadUrl, formData, {
                forceFormData: true,
                preserveState: false,
                onProgress: (event) => {
                    this.progress = event?.percent ?? 0;
                },
                onSuccess: () => {
                    this.success = 'Profile photo updated.';
                    this.file = null;
                    this.progress = 100;
                    // Clear preview so reload shows server avatar; avoids stale object URL.
                    if (this.previewUrl && this.previewUrl !== initialAvatar) {
                        URL.revokeObjectURL(this.previewUrl);
                    }
                    this.previewUrl = initialAvatar;
                },
                onError: (errors) => {
                    this.error = (errors?.avatar as string) || 'Failed to update photo.';
                },
                onFinish: () => {
                    this.uploading = false;
                    if (!this.error) {
                        router.reload({ preserveState: false });
                    }
                },
            });
        },
    };
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <h1 class="sr-only">Profile Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-8">
                <section
                    class="relative z-10 flex flex-col space-y-6 isolate"
                    aria-label="Profile picture section"
                >
                    <HeadingSmall
                        title="Profile picture"
                        description="Click your photo to change it. JPG, PNG or WEBP, up to 50 MB. Shown across the app."
                    />

                    <div
                        :key="(currentAvatarUrl ?? '') + '_' + (user?.id ?? '')"
                        x-data="profileAvatarUploader({
                            currentAvatar: $el.dataset.currentAvatar || null,
                            csrfToken: $el.dataset.csrfToken || '',
                            uploadUrl: $el.dataset.uploadUrl || '/settings/profile/avatar',
                            initials: $el.dataset.initials || '?',
                        })"
                        :data-current-avatar="currentAvatarUrl ?? ''"
                        :data-csrf-token="csrfToken"
                        data-upload-url="/settings/profile/avatar"
                        :data-initials="user ? getInitials(user.name) : '?'"
                        class="flex flex-col items-center gap-6 sm:items-start"
                    >
                        <input
                            :id="AVATAR_INPUT_ID"
                            x-ref="fileInput"
                            type="file"
                            :accept="ACCEPT"
                            class="sr-only"
                            aria-label="Choose profile photo"
                            x-on:change="onChange"
                        />

                        <div class="group relative flex shrink-0 select-none rounded-full">
                            <div
                                class="relative flex h-36 w-36 cursor-pointer items-center justify-center overflow-hidden rounded-full border-4 border-white shadow-lg transition focus-visible:ring-2 focus-visible:ring-[#2563eb] focus-visible:ring-offset-2 focus-visible:ring-offset-background dark:border-slate-800/80 dark:focus-visible:ring-[#93c5fd] dark:focus-visible:ring-offset-[#0b1b3a] sm:h-40 sm:w-40"
                                x-on:click.prevent="choose"
                                x-on:drop="onDrop"
                                x-on:dragover="onDragOver"
                                x-on:dragleave="onDragLeave"
                                x-bind:class="dragOver ? 'ring-4 ring-[#2563eb]/60 ring-offset-2 ring-offset-background dark:ring-[#93c5fd]/60 dark:ring-offset-[#0b1b3a]' : ''"
                            >
                                <span class="sr-only">Change profile picture</span>
                                <img
                                    x-show="previewUrl"
                                    x-bind:src="previewUrl"
                                    :alt="user?.name ?? 'Profile'"
                                    class="h-full w-full object-contain object-center"
                                />
                                <div
                                    x-show="!previewUrl"
                                    class="flex h-full w-full items-center justify-center bg-slate-200 text-4xl font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-300 sm:text-5xl"
                                >
                                    <span x-text="initials"></span>
                                </div>

                                <span
                                    class="absolute inset-0 flex flex-col items-center justify-center rounded-full bg-black/50 opacity-0 transition-opacity duration-200 group-hover:opacity-100 group-focus-within:opacity-100"
                                    aria-hidden="true"
                                >
                                    <Camera class="h-10 w-10 text-white sm:h-12 sm:w-12" />
                                    <span class="mt-1.5 text-sm font-medium text-white" x-text="cameraLabel()"></span>
                                </span>
                            </div>
                        </div>

                        <div x-show="file" class="flex flex-wrap items-center gap-2" x-cloak>
                            <Button
                                type="button"
                                :disabled="uploading"
                                data-test="save-avatar-button"
                                class="gap-2"
                                x-on:click="save"
                            >
                                <Check class="h-4 w-4" aria-hidden="true" />
                                <span x-text="uploading ? 'Saving…' : 'Save'"></span>
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="uploading"
                                class="gap-2"
                                x-on:click="clearSelection"
                            >
                                <X class="h-4 w-4" aria-hidden="true" />
                                Cancel
                            </Button>
                            <div x-show="uploading" class="text-sm text-muted-foreground" x-cloak>
                                Uploading… <span x-text="`${Math.round(progress ?? 0)}%`"></span>
                            </div>
                        </div>

                        <p
                            v-if="avatarStatus"
                            class="text-sm font-medium text-emerald-600 dark:text-emerald-400"
                            role="status"
                        >
                            {{ avatarStatus }}
                        </p>
                        <p
                            x-show="success"
                            class="text-sm font-medium text-emerald-600 dark:text-emerald-400"
                            x-text="success"
                            role="status"
                            x-cloak
                        ></p>
                        <p
                            x-show="error"
                            class="text-sm font-medium text-rose-600 dark:text-rose-400"
                            x-text="error"
                            role="status"
                            x-cloak
                        ></p>
                        <InputError :message="avatarValidationError" class="text-center sm:text-left" />
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
