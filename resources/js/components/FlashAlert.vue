<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { watch } from 'vue';

const page = usePage();

watch(
    () => (page.props.flash as { success?: string; error?: string; status?: string }) ?? null,
    (flash) => {
        if (!flash) return;
        if (flash.success) {
            Swal.fire({ title: 'Success', text: flash.success, icon: 'success', draggable: true });
            return;
        }
        if (flash.error) {
            Swal.fire({ title: 'Error', text: flash.error, icon: 'error', draggable: true });
            return;
        }
        if (flash.status) {
            Swal.fire({ title: 'Success', text: flash.status, icon: 'success', draggable: true });
        }
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <span class="sr-only" aria-live="polite" aria-atomic="true" />
</template>
