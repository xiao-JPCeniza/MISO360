<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

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
const page = usePage();
const canAccessFooter = computed(
    () =>
        ['admin', 'super_admin'].includes(
            (page.props.auth as { user?: { role?: string } })?.user?.role ?? '',
        ),
);
const contentPadding = computed(() =>
    props.breadcrumbs && props.breadcrumbs.length > 1 ? 'pt-28' : 'pt-20',
);
const contentBottomPadding = computed(() => (canAccessFooter.value ? 'pb-24' : 'pb-6'));
</script>

<template>
    <AppShell class="flex-col" variant="header">
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent :class="['px-6 md:pt-24', contentPadding, contentBottomPadding]">
            <slot />
        </AppContent>
        <AppFooter v-if="canAccessFooter" />
    </AppShell>
</template>
