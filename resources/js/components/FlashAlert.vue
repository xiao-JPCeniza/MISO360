<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import {
    flashHasContent,
    flashPayloadSignature,
    showFlashToasts,
} from '@/lib/notifications';
import type { FlashMessages } from '@/types';

const page = usePage();

const flash = computed(() => (page.props.flash ?? null) as FlashMessages | null);

const lastShownSignature = ref<string>('');
const liveMessage = ref<string>('');

function firstFlashText(value: FlashMessages | null): string {
    if (!value) {
        return '';
    }

    const candidates = [value.error, value.warning, value.success, value.status, value.info];

    for (const raw of candidates) {
        if (raw === null || raw === undefined) {
            continue;
        }

        const text = String(raw).trim();
        if (text.length > 0) {
            return text;
        }
    }

    return '';
}

watch(
    flash,
    (value) => {
        if (!flashHasContent(value)) {
            lastShownSignature.value = '';
            liveMessage.value = '';

            return;
        }

        const signature = flashPayloadSignature(value);
        if (signature === lastShownSignature.value) {
            return;
        }

        lastShownSignature.value = signature;
        liveMessage.value = firstFlashText(value);
        showFlashToasts(value);
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <div class="sr-only" role="status" aria-live="polite" aria-atomic="true">
        <template v-if="liveMessage">{{ liveMessage }}</template>
    </div>
</template>
