<script setup lang="ts">
import { AlertTriangle, Ban, CircleCheck } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { OccupancyState } from '@/types';

const props = defineProps<{
    state: OccupancyState;
}>();

const meta = computed(() => {
    switch (props.state) {
        case 'full':
            return {
                label: 'Full',
                icon: Ban,
                class: 'border-transparent bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
            };
        case 'warning':
            return {
                label: 'Nearly full',
                icon: AlertTriangle,
                class: 'border-transparent bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
            };
        default:
            return {
                label: 'Space available',
                icon: CircleCheck,
                class: 'border-transparent bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
            };
    }
});
</script>

<template>
    <Badge variant="outline" :class="meta.class">
        <component :is="meta.icon" />
        {{ meta.label }}
    </Badge>
</template>
