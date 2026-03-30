<script setup lang="ts">
import AppLayout from '@/layouts/app/AppHeaderLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    fullWidth?: boolean;
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    fullWidth: false,
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs" :fullWidth="fullWidth">
        <slot />
    </AppLayout>
</template>
