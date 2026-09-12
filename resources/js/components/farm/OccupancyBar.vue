<script setup lang="ts">
import { computed } from 'vue';
import type { OccupancyState } from '@/types';

const props = defineProps<{
    occupancy: number;
    capacity: number;
    state: OccupancyState;
    showLabel?: boolean;
}>();

const percent = computed(() =>
    props.capacity > 0
        ? Math.min(100, Math.round((props.occupancy / props.capacity) * 100))
        : 100,
);

const barClass = computed(() => {
    switch (props.state) {
        case 'full':
            return 'bg-red-500';
        case 'warning':
            return 'bg-amber-500';
        default:
            return 'bg-emerald-500';
    }
});
</script>

<template>
    <div class="flex items-center gap-3">
        <div
            class="bg-muted h-2 flex-1 overflow-hidden rounded-full"
            role="progressbar"
            :aria-valuenow="occupancy"
            aria-valuemin="0"
            :aria-valuemax="capacity"
            :aria-label="`${occupancy} of ${capacity} places used`"
        >
            <div
                class="h-full rounded-full transition-[width]"
                :class="barClass"
                :style="{ width: `${percent}%` }"
            />
        </div>
        <span
            v-if="showLabel !== false"
            class="text-muted-foreground w-16 shrink-0 text-right text-xs tabular-nums"
        >
            {{ occupancy }} / {{ capacity }}
        </span>
    </div>
</template>
