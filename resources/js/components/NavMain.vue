<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useActiveUrl } from '@/composables/useActiveUrl';
import { type NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { urlIsActive } = useActiveUrl();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel
            class="text-[0.65rem] uppercase tracking-[0.25em] text-[#93c5fd]"
        >
            Main Menu
        </SidebarGroupLabel>
        <SidebarMenu class="mt-2 space-y-1">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="urlIsActive(item.href)"
                    :tooltip="item.title"
                    class="text-white/80 transition-colors duration-200 hover:bg-white/10 hover:text-white data-[active=true]:bg-white/15 data-[active=true]:text-white data-[active=true]:shadow-[inset_0_0_0_1px_rgba(147,197,253,0.35)]"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span
                            class="group-data-[collapsible=icon]/sidebar-wrapper:hidden"
                        >
                            {{ item.title }}
                        </span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
