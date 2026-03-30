<script setup lang="ts">
import { AlertCircle } from 'lucide-vue-next';

defineProps<{
    message?: string;
}>();
</script>

<template>
    <Transition name="field-error" appear>
        <div
            v-if="message"
            :key="message"
            class="flex gap-1.5 text-sm text-destructive dark:text-red-400"
            role="alert"
            aria-live="polite"
        >
            <AlertCircle class="mt-0.5 size-4 shrink-0 opacity-90" aria-hidden="true" />
            <p class="min-w-0 leading-snug">
                {{ message }}
            </p>
        </div>
    </Transition>
</template>
