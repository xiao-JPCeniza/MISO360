<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { AvatarRoot } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <AvatarRoot
    data-slot="avatar"
    :class="cn('relative flex size-8 shrink-0 overflow-hidden rounded-full avatar-show-full', props.class)"
  >
    <slot />
  </AvatarRoot>
</template>

<style scoped>
.avatar-show-full :deep(img) {
    object-fit: contain !important;
    object-position: center;
}
</style>
