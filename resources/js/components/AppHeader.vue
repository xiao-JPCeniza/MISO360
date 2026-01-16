<script setup lang="ts">
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Link, usePage } from '@inertiajs/vue3';
import { ClipboardList, LayoutGrid, Menu, Package } from 'lucide-vue-next';
import { computed } from 'vue';

import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useActiveUrl } from '@/composables/useActiveUrl';
import { getInitials } from '@/composables/useInitials';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, NavItem } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const { urlIsActive } = useActiveUrl();

function activeItemClasses(url: NonNullable<InertiaLinkProps['href']>) {
    return urlIsActive(url)
        ? 'text-[#2563eb] dark:text-[#93c5fd] bg-[#2563eb]/10 dark:bg-white/10'
        : 'text-[#0b1b3a] dark:text-white';
}

const mainNavItems = computed(() => {
    const isAdmin = auth.value?.user?.role === 'admin';

    const baseItems: NavItem[] = [
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
            title: 'Submit a Request Ticket',
            href: '/submit-request',
            icon: Package, // Using Package icon for now, can be changed to a more appropriate icon
        },
    ];

    // Add admin-only items
    if (isAdmin) {
        baseItems.push({
            title: 'Inventory',
            href: '/inventory',
            icon: Package,
        });
    }

    return baseItems;
});
</script>

<template>
    <div>
        <header
            class="fixed top-0 z-50 w-full border-b border-white/10 bg-white/95 text-[#0b1b3a] shadow-sm shadow-black/10 backdrop-blur transition-colors dark:border-white/5 dark:bg-[#0b1b3a]/95 dark:text-white"
        >
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl lg:px-6">
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="mr-2 h-9 w-9 text-[#0b1b3a] hover:bg-[#2563eb]/10 dark:text-white dark:hover:bg-white/10"
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent
                            side="left"
                            class="w-[300px] border-r border-white/10 bg-[#0b1b3a] p-6 text-white"
                        >
                            <SheetTitle class="sr-only"
                                >Navigation Menu</SheetTitle
                            >
                            <SheetHeader class="flex justify-start text-left">
                                <div class="flex items-center gap-3">
                                    <img
                                        src="/favicon.svg"
                                        alt="System logo"
                                        class="h-10 w-10 rounded-full bg-white p-1"
                                    />
                                    <div>
                                        <p
                                            class="text-xs uppercase tracking-[0.2em] text-[#93c5fd]"
                                        >
                                            MSO 360
                                        </p>
                                        <p class="text-sm font-semibold">
                                            Management Services Office
                                        </p>
                                    </div>
                                </div>
                            </SheetHeader>
                            <nav class="mt-8 space-y-2">
                                <Link
                                    v-for="item in mainNavItems"
                                    :key="item.title"
                                    :href="item.href"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors"
                                    :class="[
                                        urlIsActive(item.href)
                                            ? 'bg-white/15 text-white'
                                            : 'text-white/80 hover:bg-white/10 hover:text-white',
                                    ]"
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                        class="h-4 w-4"
                                    />
                                    {{ item.title }}
                                </Link>
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="dashboard()" class="flex items-center gap-3">
                    <img
                        src="/favicon.svg"
                        alt="System logo"
                        class="h-10 w-10 rounded-full bg-white p-1"
                    />
                    <div class="leading-tight">
                        <p class="text-xs uppercase tracking-[0.2em] text-[#2563eb]">
                            MSO 360
                        </p>
                        <p class="text-sm font-semibold">
                            Management Services Office
                        </p>
                    </div>
                </Link>

                <nav class="ml-10 hidden items-center gap-2 lg:flex">
                    <Link
                        v-for="item in mainNavItems"
                        :key="item.title"
                        :href="item.href"
                        class="group flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition-colors"
                        :class="[
                            activeItemClasses(item.href),
                            'hover:bg-[#2563eb]/10 dark:hover:bg-white/10',
                        ]"
                    >
                        <component
                            v-if="item.icon"
                            :is="item.icon"
                            class="h-4 w-4"
                        />
                        <span>{{ item.title }}</span>
                        <span
                            v-if="urlIsActive(item.href)"
                            class="ml-1 h-1.5 w-1.5 rounded-full bg-[#2563eb] dark:bg-[#93c5fd]"
                        ></span>
                    </Link>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-[#2563eb] dark:focus-within:ring-[#93c5fd]"
                            >
                                <Avatar class="size-8 overflow-hidden rounded-full">
                                    <AvatarImage
                                        v-if="auth.user.avatar"
                                        :src="auth.user.avatar"
                                        :alt="auth.user.name"
                                    />
                                    <AvatarFallback
                                        class="rounded-lg bg-[#2563eb]/10 font-semibold text-[#0b1b3a] dark:bg-white/10 dark:text-white"
                                    >
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </header>

        <div
            v-if="props.breadcrumbs.length > 1"
            class="fixed top-16 z-40 w-full border-b border-white/10 bg-white/90 backdrop-blur dark:border-white/5 dark:bg-[#0b1b3a]/90"
        >
            <div
                class="mx-auto flex h-12 w-full items-center justify-start px-4 text-sm text-slate-500 md:max-w-7xl lg:px-6 dark:text-slate-300"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>
