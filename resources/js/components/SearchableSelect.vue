<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type SelectOption = {
    id: number | string;
    name: string;
    isLegacy?: boolean;
};

const props = withDefaults(defineProps<{
    modelValue: number | string | null | undefined;
    options: SelectOption[];
    placeholder?: string;
    searchPlaceholder?: string;
    emptyLabel?: string;
    ariaLabel?: string;
    disabled?: boolean;
    required?: boolean;
    allowClear?: boolean;
}>(), {
    placeholder: 'Select an option',
    searchPlaceholder: 'Search options',
    emptyLabel: 'No results found.',
    ariaLabel: 'Searchable select',
    disabled: false,
    required: false,
    allowClear: false,
});

const emit = defineEmits<{
    (event: 'update:modelValue', value: number | string | null): void;
}>();

const open = ref(false);
const query = ref('');
const wrapperRef = ref<HTMLElement | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);

const selectedOption = computed(() =>
    props.options.find((option) => String(option.id) === String(props.modelValue)),
);

const filteredOptions = computed(() => {
    const normalizedQuery = query.value.trim().toLowerCase();
    if (normalizedQuery === '') {
        return props.options;
    }

    return props.options.filter((option) =>
        option.name.toLowerCase().includes(normalizedQuery),
    );
});

const buttonLabel = computed(() => {
    if (!selectedOption.value) {
        return props.placeholder;
    }

    if (selectedOption.value.isLegacy) {
        return `${selectedOption.value.name} (inactive)`;
    }

    return selectedOption.value.name;
});

function onDocumentClick(event: MouseEvent) {
    if (!wrapperRef.value) {
        return;
    }

    const target = event.target as Node | null;
    if (target && wrapperRef.value.contains(target)) {
        return;
    }

    open.value = false;
}

function toggleOpen() {
    if (props.disabled) {
        return;
    }

    open.value = !open.value;
    if (open.value) {
        query.value = '';
        requestAnimationFrame(() => searchInputRef.value?.focus());
    }
}

function closeMenu() {
    open.value = false;
}

function selectOption(option: SelectOption) {
    emit('update:modelValue', option.id);
    closeMenu();
}

function clearSelection() {
    emit('update:modelValue', null);
    closeMenu();
}

onMounted(() => {
    window.addEventListener('mousedown', onDocumentClick);
});

onBeforeUnmount(() => {
    window.removeEventListener('mousedown', onDocumentClick);
});
</script>

<template>
    <div ref="wrapperRef" class="searchable-select">
        <button
            type="button"
            class="searchable-select__trigger"
            :class="{ 'searchable-select__trigger--placeholder': !selectedOption }"
            :aria-haspopup="'listbox'"
            :aria-expanded="open"
            :aria-label="ariaLabel"
            :disabled="disabled"
            @click="toggleOpen"
        >
            <span class="truncate">{{ buttonLabel }}</span>
            <span class="searchable-select__icon" aria-hidden="true">
                {{ open ? '▴' : '▾' }}
            </span>
        </button>

        <input
            v-if="required"
            class="searchable-select__required-proxy"
            tabindex="-1"
            :value="selectedOption ? String(selectedOption.id) : ''"
            required
        />

        <div v-if="open" class="searchable-select__menu">
            <input
                ref="searchInputRef"
                v-model="query"
                type="text"
                class="searchable-select__search"
                :placeholder="searchPlaceholder"
                :aria-label="searchPlaceholder"
                @keydown.esc.prevent="closeMenu"
            />
            <ul class="searchable-select__list" role="listbox">
                <li v-if="allowClear && selectedOption">
                    <button
                        type="button"
                        class="searchable-select__option searchable-select__option--clear"
                        @click="clearSelection"
                    >
                        Clear selection
                    </button>
                </li>
                <li v-for="option in filteredOptions" :key="String(option.id)">
                    <button
                        type="button"
                        class="searchable-select__option"
                        :class="{
                            'searchable-select__option--active':
                                String(option.id) === String(modelValue),
                        }"
                        @click="selectOption(option)"
                    >
                        {{ option.isLegacy ? `${option.name} (inactive)` : option.name }}
                    </button>
                </li>
                <li v-if="filteredOptions.length === 0" class="searchable-select__empty">
                    {{ emptyLabel }}
                </li>
            </ul>
        </div>
    </div>
</template>

<style scoped>
.searchable-select {
    position: relative;
}

.searchable-select__trigger {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    border-radius: 0.875rem;
    border: 1px solid color-mix(in srgb, var(--border) 88%, transparent);
    background: color-mix(in srgb, var(--background) 95%, var(--card));
    color: var(--foreground);
    padding: 0.625rem 0.75rem;
    text-align: left;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.searchable-select__trigger:focus-visible {
    outline: none;
    border-color: var(--ring);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--ring) 25%, transparent);
}

.searchable-select__trigger:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.searchable-select__trigger--placeholder {
    color: color-mix(in srgb, var(--muted-foreground) 90%, var(--foreground) 10%);
}

.searchable-select__icon {
    color: color-mix(in srgb, var(--foreground) 78%, transparent);
    font-size: 0.75rem;
}

.searchable-select__menu {
    position: absolute;
    z-index: 30;
    margin-top: 0.5rem;
    width: 100%;
    border-radius: 0.875rem;
    border: 1px solid color-mix(in srgb, var(--border) 90%, transparent);
    background: color-mix(in srgb, var(--card) 97%, var(--background));
    box-shadow: 0 12px 24px -12px color-mix(in srgb, #020617 36%, transparent);
    overflow: hidden;
}

.searchable-select__search {
    width: 100%;
    border: 0;
    border-bottom: 1px solid color-mix(in srgb, var(--border) 80%, transparent);
    background: color-mix(in srgb, var(--background) 97%, var(--card));
    color: var(--foreground);
    padding: 0.65rem 0.75rem;
}

.searchable-select__search:focus {
    outline: none;
    box-shadow: inset 0 -1px 0 color-mix(in srgb, var(--ring) 65%, transparent);
}

.searchable-select__list {
    max-height: 14rem;
    overflow-y: auto;
}

.searchable-select__option {
    width: 100%;
    border: 0;
    background: transparent;
    color: var(--foreground);
    cursor: pointer;
    padding: 0.625rem 0.75rem;
    text-align: left;
    font-size: 0.875rem;
}

.searchable-select__option:hover {
    background: color-mix(in srgb, var(--primary) 12%, var(--background));
}

.searchable-select__option--active {
    background: color-mix(in srgb, var(--primary) 18%, var(--background));
    color: color-mix(in srgb, var(--foreground) 92%, var(--primary));
    font-weight: 700;
}

.searchable-select__option--clear {
    color: color-mix(in srgb, var(--destructive) 85%, var(--foreground) 15%);
}

.searchable-select__empty {
    color: var(--muted-foreground);
    font-size: 0.8125rem;
    padding: 0.75rem;
}

.searchable-select__required-proxy {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}

.dark .searchable-select__menu {
    border-color: color-mix(in srgb, var(--border) 88%, #ffffff 12%);
    background: color-mix(in srgb, var(--card) 88%, var(--background));
}

.dark .searchable-select__search {
    border-color: color-mix(in srgb, var(--border) 80%, #ffffff 20%);
    background: color-mix(in srgb, var(--background) 78%, var(--card));
}
</style>
