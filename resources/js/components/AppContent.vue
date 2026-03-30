<script setup lang="ts">
import { computed } from 'vue';

import { SidebarInset } from '@/components/ui/sidebar';

interface Props {
    variant?: 'header' | 'sidebar';
    class?: string | string[] | Record<string, boolean>;
    fullWidth?: boolean;
}

const props = defineProps<Props>();
const className = computed(() => props.class);
const maxWidthClass = computed(() => (props.fullWidth ? 'max-w-none' : 'max-w-7xl'));
</script>

<template>
    <SidebarInset v-if="props.variant === 'sidebar'" :class="className">
        <slot />
    </SidebarInset>
    <main
        v-else
        class="mx-auto flex h-full w-full flex-1 flex-col gap-4 rounded-xl"
        :class="[maxWidthClass, className]"
    >
        <slot />
    </main>
</template>
