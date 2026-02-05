<script setup lang="ts">
import type { AvatarImageProps } from "reka-ui"
import { AvatarImage } from "reka-ui"

const props = defineProps<AvatarImageProps>()
</script>

<template>
  <AvatarImage
    data-slot="avatar-image"
    v-bind="props"
    class="avatar-image-full aspect-square size-full object-contain object-center"
  >
    <slot />
  </AvatarImage>
</template>

<style scoped>
/* Ensure full picture is shown, not zoomed/cropped */
.avatar-image-full,
.avatar-image-full :deep(img) {
  object-fit: contain !important;
  object-position: center;
}
</style>
