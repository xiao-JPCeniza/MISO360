<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';

type Appearance = 'light' | 'dark' | 'system';

const { appearance, updateAppearance } = useAppearance();

const options: { value: Appearance; Icon: typeof Sun; label: string }[] = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
];

const currentIcon = computed(() => {
    if (appearance.value === 'dark') return Moon;
    if (appearance.value === 'light') return Sun;
    return Monitor;
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="size-11 min-h-11 min-w-11 shrink-0 text-foreground hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring sm:size-9 sm:min-h-9 sm:min-w-9"
                aria-label="Toggle theme (light, dark, or system)"
            >
                <component
                    :is="currentIcon"
                    class="size-5"
                />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="min-w-40">
            <DropdownMenuItem
                v-for="{ value, Icon, label } in options"
                :key="value"
                @select.prevent="updateAppearance(value)"
                :class="[
                    'cursor-pointer',
                    appearance === value && 'bg-accent font-medium',
                ]"
            >
                <component :is="Icon" class="mr-2 size-4" />
                {{ label }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
