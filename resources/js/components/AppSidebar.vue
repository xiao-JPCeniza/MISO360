<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ClipboardList, LayoutGrid, Package, ShieldCheck } from 'lucide-vue-next';

import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';

const page = usePage();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'List of Requests',
            href: '/requests',
            icon: ClipboardList,
        },
        {
            title: 'Inventory',
            href: '/inventory',
            icon: Package,
        },
    ];

    if (isAdmin.value) {
        items.push({
            title: 'Admin Dashboard',
            href: '/admin/dashboard',
            icon: ShieldCheck,
        });
    }

    return items;
});

const homeRoute = computed(() => (isAdmin.value ? '/admin/dashboard' : dashboard()));
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="text-white">
        <SidebarHeader class="border-b border-white/10 px-4 pb-4 pt-5">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="h-auto justify-start rounded-2xl px-2 py-2 text-white transition-colors hover:bg-white/10"
                    >
                        <Link :href="homeRoute" class="flex items-center gap-3">
                            <img
                                src="/favicon.svg"
                                alt="System logo"
                                class="h-11 w-11 rounded-full bg-white/90 p-1 shadow-sm shadow-black/20"
                            />
                            <div
                                class="grid text-left leading-tight group-data-[collapsible=icon]/sidebar-wrapper:hidden"
                            >
                                <span
                                    class="text-xs uppercase tracking-[0.2em] text-[#93c5fd]"
                                >
                                    MSO 360
                                </span>
                                <span class="text-sm font-semibold text-white">
                                    Management Services Office
                                </span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="px-2 py-3">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter class="border-t border-white/10 px-2 pb-3 pt-3">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
