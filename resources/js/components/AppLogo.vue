<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div class="flex items-center gap-3">
        <div
            class="flex size-10 items-center justify-center rounded-full bg-white/90 p-1 text-[#0b1b3a] shadow-sm shadow-black/20"
        >
            <AppLogoIcon class="size-6 fill-current text-[#0b1b3a]" />
        </div>
        <div class="grid flex-1 text-left text-sm leading-tight">
            <span class="text-xs uppercase tracking-[0.2em] text-[#93c5fd]">
                MSO 360
            </span>
            <span class="text-sm font-semibold text-sidebar-foreground">
                Management Services Office
            </span>
        </div>
    </div>
</template>
