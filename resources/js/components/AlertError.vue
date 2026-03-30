<script setup lang="ts">
import { AlertCircle, AlertTriangle, Info } from 'lucide-vue-next';
import { computed } from 'vue';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

interface Props {
    errors: string[];
    title?: string;
    variant?: 'destructive' | 'warning' | 'info';
}

const props = withDefaults(defineProps<Props>(), {
    title: undefined,
    variant: 'destructive',
});

const uniqueErrors = computed(() => Array.from(new Set(props.errors)));

const resolvedTitle = computed(() => {
    if (props.title !== undefined && props.title.length > 0) {
        return props.title;
    }

    if (props.variant === 'warning') {
        return 'Please review.';
    }

    if (props.variant === 'info') {
        return 'Heads up.';
    }

    return 'Something went wrong.';
});

const alertVariant = computed(() =>
    props.variant === 'destructive' ? 'destructive' : props.variant,
);

const transitionName = computed(() => `form-alert-${props.variant}`);

const Icon = computed(() => {
    if (props.variant === 'warning') {
        return AlertTriangle;
    }

    if (props.variant === 'info') {
        return Info;
    }

    return AlertCircle;
});

const errorSignature = computed(() => uniqueErrors.value.join('\u0000'));
</script>

<template>
    <Transition :name="transitionName" appear>
        <Alert v-if="uniqueErrors.length > 0" :key="errorSignature" :variant="alertVariant">
            <component :is="Icon" class="size-4" />
            <AlertTitle>{{ resolvedTitle }}</AlertTitle>
            <AlertDescription>
                <ul class="list-inside list-disc text-sm">
                    <li v-for="(error, index) in uniqueErrors" :key="index">
                        {{ error }}
                    </li>
                </ul>
            </AlertDescription>
        </Alert>
    </Transition>
</template>
