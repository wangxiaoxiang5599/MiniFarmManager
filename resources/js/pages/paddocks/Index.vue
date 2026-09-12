<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import EmptyState from '@/components/farm/EmptyState.vue';
import OccupancyBadge from '@/components/farm/OccupancyBadge.vue';
import OccupancyBar from '@/components/farm/OccupancyBar.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { create, index, show } from '@/routes/paddocks';
import type { Paddock } from '@/types';

defineProps<{
    paddocks: Paddock[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Paddocks', href: index() }],
    },
});
</script>

<template>
    <Head title="Paddocks" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Paddocks"
                description="Where the animals live and how much room is left."
            />
            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    Add paddock
                </Link>
            </Button>
        </div>

        <EmptyState
            v-if="paddocks.length === 0"
            title="No paddocks yet"
            description="Create your first paddock to start placing animals."
        >
            <Button as-child variant="outline">
                <Link :href="create()">Add paddock</Link>
            </Button>
        </EmptyState>

        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="paddock in paddocks"
                :key="paddock.id"
                :href="show(paddock)"
                class="focus-visible:ring-ring rounded-xl focus-visible:ring-2 focus-visible:outline-none"
            >
                <Card
                    class="h-full gap-4 py-5 transition-colors hover:bg-accent/40"
                    :class="{
                        'border-amber-400/70': paddock.occupancy_state === 'warning',
                        'border-red-400/70': paddock.occupancy_state === 'full',
                    }"
                >
                    <CardContent class="flex flex-col gap-4 px-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate font-semibold">
                                    {{ paddock.name }}
                                </h3>
                                <p class="text-muted-foreground text-sm">
                                    {{ paddock.remaining_capacity }} of
                                    {{ paddock.capacity }} places free
                                </p>
                            </div>
                            <OccupancyBadge :state="paddock.occupancy_state" />
                        </div>
                        <OccupancyBar
                            :occupancy="paddock.occupancy"
                            :capacity="paddock.capacity"
                            :state="paddock.occupancy_state"
                        />
                    </CardContent>
                </Card>
            </Link>
        </div>
    </div>
</template>
