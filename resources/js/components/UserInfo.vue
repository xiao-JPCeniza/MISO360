<script setup lang="ts">
import { computed } from 'vue';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';

interface Props {
    user: User;
    showEmail?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

/** Same-origin avatar URL so the photo shows in header dropdown and sidebar after upload. */
const avatarUrl = computed(() => {
    const u = props.user;
    const path = u.avatar && String(u.avatar).trim() !== '' ? u.avatar : null;
    if (path) {
        const normalized = path.startsWith('/') ? path.slice(1) : path;
        return `/storage/${normalized}`;
    }
    return u.avatar_url ?? null;
});
const showAvatar = computed(() => Boolean(avatarUrl.value));
</script>

<template>
    <Avatar class="avatar-profile h-8 w-8 overflow-hidden rounded-full">
        <AvatarImage v-if="showAvatar" :src="avatarUrl!" :alt="user.name" />
        <AvatarFallback class="rounded-full text-black dark:text-white">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{
            user.email
        }}</span>
    </div>
</template>
