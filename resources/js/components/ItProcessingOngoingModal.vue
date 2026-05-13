<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import Icon from '@/components/Icon.vue';
import { ATTACHMENT_ACCEPT } from '@/constants/attachmentUpload';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type ModalAttachment = {
    id?: string | null;
    name: string;
    url?: string | null;
    size?: number | null;
    mime?: string | null;
    uploadedAt?: string | null;
};

const props = defineProps<{
    open: boolean;
    canEdit: boolean;
    saveUrl: string;
    notes: string | null;
    attachments: ModalAttachment[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const hasSavedData = computed(() => {
    if (String(props.notes ?? '').trim() !== '') {
        return true;
    }
    return (props.attachments ?? []).length > 0;
});

const isEditing = ref<boolean>(props.canEdit ? !hasSavedData.value : false);

watch(
    () => props.open,
    (open) => {
        if (!open) {
            form.reset('attachments');
            deleteIds.value = [];
            isEditing.value = props.canEdit ? !hasSavedData.value : false;
            return;
        }

        form.notes = String(props.notes ?? '');
        deleteIds.value = [];
        isEditing.value = props.canEdit ? !hasSavedData.value : false;
    },
);

const deleteIds = ref<string[]>([]);

const form = useForm<{
    notes: string;
    attachments: File[];
    deleteAttachmentIds: string[];
}>({
    notes: String(props.notes ?? ''),
    attachments: [],
    deleteAttachmentIds: [],
});

const accept = ATTACHMENT_ACCEPT;

function close(): void {
    emit('update:open', false);
}

function toggleDelete(id: string): void {
    if (!id) return;
    if (deleteIds.value.includes(id)) {
        deleteIds.value = deleteIds.value.filter((x) => x !== id);
        return;
    }
    deleteIds.value = [...deleteIds.value, id];
}

function onFilesSelected(event: Event): void {
    const input = event.target as HTMLInputElement | null;
    const files = input?.files ? Array.from(input.files) : [];
    form.attachments = files;
}

function startEdit(): void {
    if (!props.canEdit) return;
    isEditing.value = true;
}

function submit(): void {
    if (!props.canEdit || !isEditing.value) return;

    form.deleteAttachmentIds = [...deleteIds.value];

    form.post(props.saveUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['ticket'] });
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>IT Processing (Processed)</DialogTitle>
                <DialogDescription>
                    Upload supporting documents and optionally add notes. Files and notes are tied to this control ticket.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4">
                <div class="grid gap-1.5">
                    <label class="text-xs font-semibold text-muted-foreground">
                        Notes (optional)
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="5"
                        class="min-h-28 w-full resize-y rounded-md border bg-background px-3 py-2 text-sm leading-relaxed disabled:cursor-not-allowed disabled:bg-muted/40"
                        :disabled="!canEdit || !isEditing"
                        placeholder="Add additional notes…"
                    />
                    <p v-if="form.errors.notes" class="text-xs text-rose-600">
                        {{ form.errors.notes }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold text-muted-foreground">
                            Documents
                        </p>
                        <span class="text-[11px] text-muted-foreground">
                            Allowed: PDF, Word, Excel
                        </span>
                    </div>

                    <div class="grid gap-2 rounded-lg border bg-muted/10 p-3">
                        <div v-if="attachments.length" class="grid gap-2">
                            <div
                                v-for="att in attachments"
                                :key="att.id ?? att.name"
                                class="flex items-center justify-between gap-3 rounded-md border bg-background px-3 py-2"
                            >
                                <div class="min-w-0">
                                    <a
                                        v-if="att.url"
                                        class="block truncate text-sm font-medium text-blue-600 hover:text-blue-500"
                                        :href="att.url"
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        {{ att.name }}
                                    </a>
                                    <p v-else class="truncate text-sm font-medium">
                                        {{ att.name }}
                                    </p>
                                    <p v-if="att.uploadedAt" class="text-[11px] text-muted-foreground">
                                        Uploaded {{ att.uploadedAt }}
                                    </p>
                                </div>

                                <Button
                                    v-if="canEdit && isEditing && att.id"
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    class="shrink-0"
                                    @click="toggleDelete(String(att.id))"
                                >
                                    <Icon
                                        :name="deleteIds.includes(String(att.id)) ? 'undo2' : 'trash2'"
                                        class="mr-2"
                                    />
                                    {{ deleteIds.includes(String(att.id)) ? 'Undo' : 'Delete' }}
                                </Button>
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            No documents uploaded yet.
                        </p>

                        <div v-if="canEdit && isEditing" class="grid gap-1.5">
                            <label class="text-xs font-semibold text-muted-foreground">
                                Add files (optional)
                            </label>
                            <input
                                type="file"
                                multiple
                                :accept="accept"
                                class="block w-full text-sm file:mr-4 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-2 file:text-sm file:font-semibold hover:file:bg-muted/70"
                                @change="onFilesSelected"
                            />
                            <p class="text-[11px] text-muted-foreground">
                                Same file types as ticket submissions (e.g. PDF, Office, images, video). Up to 20 MB each.
                            </p>
                            <p v-if="form.errors.attachments" class="text-xs text-rose-600">
                                {{ form.errors.attachments }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button type="button" variant="outline" @click="close">
                    Close
                </Button>

                <Button
                    v-if="canEdit && hasSavedData && !isEditing"
                    type="button"
                    @click="startEdit"
                >
                    Edit
                </Button>

                <Button
                    v-if="canEdit && isEditing"
                    type="button"
                    :disabled="form.processing"
                    @click="submit"
                >
                    {{ form.processing ? 'Saving…' : hasSavedData ? 'Save changes' : 'Save' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

