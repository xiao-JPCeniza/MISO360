<script setup lang="ts">
import { computed } from 'vue';

import AppContent from '@/components/AppContent.vue';
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
const contentPadding = computed(() =>
    props.breadcrumbs && props.breadcrumbs.length > 1 ? 'pt-28' : 'pt-20',
);
</script>

<template>
    <AppShell class="flex-col" variant="header">
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent :class="['px-6 pb-24 md:pt-24', contentPadding]">
            <slot />
        </AppContent>
        <AppFooter />
    </AppShell>
</template>
