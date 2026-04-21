<script setup lang="ts">
import { onClickOutside } from '@vueuse/core';
import { computed, ref, useTemplateRef, watch } from 'vue';

import Icon from '@/components/Icon.vue';

type AssignedStaffOption = {
    id: number | string;
    name: string;
};

const selectedIds = defineModel<string[]>({ required: true });

const props = withDefaults(
    defineProps<{
        staffOptions: AssignedStaffOption[];
        disabled?: boolean;
    }>(),
    {
        disabled: false,
    },
);

const open = ref(false);
const rootRef = useTemplateRef<HTMLDivElement>('root');

onClickOutside(rootRef, () => {
    open.value = false;
});

watch(
    () => props.disabled,
    (isDisabled) => {
        if (isDisabled) {
            open.value = false;
        }
    },
);

const summary = computed((): string => {
    if (props.staffOptions.length === 0) {
        return 'No IT staff available';
    }

    const ids = selectedIds.value;
    if (ids.length === 0) {
        return 'Select IT staff…';
    }

    const names = props.staffOptions
        .filter((s) => ids.includes(String(s.id)))
        .map((s) => s.name);

    if (names.length === 0) {
        return `${ids.length} selected`;
    }

    if (names.length === 1) {
        return names[0] ?? '';
    }

    if (names.length === 2) {
        return `${names[0]}, ${names[1]}`;
    }

    return `${names[0]}, ${names[1]} +${names.length - 2} more`;
});

function toggleId(id: string): void {
    if (props.disabled) {
        return;
    }

    const cur = [...selectedIds.value];
    const idx = cur.indexOf(id);
    if (idx === -1) {
        cur.push(id);
    } else {
        cur.splice(idx, 1);
    }

    selectedIds.value = cur;
}

function isSelected(id: string): boolean {
    return selectedIds.value.includes(id);
}
</script>

<template>
    <div ref="root" class="relative w-full">
        <button
            type="button"
            class="flex h-8 w-full items-center justify-between gap-2 rounded border border-white/30 bg-white px-2 text-left text-[11px] text-slate-900 transition hover:border-white/50 focus:border-white/60 focus:ring-2 focus:ring-white/20 focus:outline-none disabled:cursor-not-allowed disabled:bg-white/70 disabled:text-slate-500"
            :disabled="disabled || staffOptions.length === 0"
            :aria-expanded="open"
            aria-haspopup="listbox"
            @click="open = !open"
        >
            <span class="min-w-0 flex-1 truncate">{{ summary }}</span>
            <Icon
                name="chevronDown"
                class="size-4 shrink-0 text-slate-500 transition-transform"
                :class="{ 'rotate-180': open }"
            />
        </button>
        <div
            v-show="open && staffOptions.length > 0"
            class="absolute right-0 left-0 z-50 mt-1 max-h-56 overflow-y-auto overscroll-contain rounded-md border border-slate-200 bg-white py-1 shadow-lg shadow-black/20"
            role="listbox"
            aria-multiselectable="true"
            tabindex="-1"
            @keydown.escape.prevent="open = false"
        >
            <button
                v-for="staff in staffOptions"
                :key="staff.id"
                type="button"
                role="option"
                :aria-selected="isSelected(String(staff.id))"
                class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-[11px] text-slate-800 hover:bg-slate-50"
                :class="
                    isSelected(String(staff.id))
                        ? 'bg-sky-50 text-slate-900'
                        : ''
                "
                @click="toggleId(String(staff.id))"
            >
                <span
                    class="flex size-4 shrink-0 items-center justify-center rounded border border-slate-300"
                    :class="
                        isSelected(String(staff.id))
                            ? 'border-[#0b2e59] bg-[#0b2e59] text-white'
                            : 'bg-white'
                    "
                >
                    <Icon
                        v-if="isSelected(String(staff.id))"
                        name="check"
                        class="size-3 text-white"
                        :stroke-width="3"
                    />
                </span>
                <span class="min-w-0 flex-1 truncate">{{ staff.name }}</span>
            </button>
        </div>
    </div>
</template>
