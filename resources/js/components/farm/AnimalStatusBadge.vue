<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { AnimalStatus } from '@/types';

const props = defineProps<{
    status: AnimalStatus;
    label: string;
}>();

const classes = computed(() => {
    switch (props.status) {
        case 'sold':
            return 'border-transparent bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-200';
        case 'deceased':
            return 'border-transparent bg-neutral-200 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300';
        default:
            return 'border-transparent bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200';
    }
});
</script>

<template>
    <Badge variant="outline" :class="classes">{{ label }}</Badge>
</template>
