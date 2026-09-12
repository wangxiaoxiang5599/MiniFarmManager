<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, ArrowRight, Fence, PawPrint, Plus } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/farm/EmptyState.vue';
import OccupancyBadge from '@/components/farm/OccupancyBadge.vue';
import OccupancyBar from '@/components/farm/OccupancyBar.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { create as createAnimal, index as animalsIndex, show as showAnimal } from '@/routes/animals';
import { create as createPaddock, index as paddocksIndex, show as showPaddock } from '@/routes/paddocks';
import type { AnimalMovement, Paddock } from '@/types';

const props = defineProps<{
    stats: {
        active_animals: number;
        unplaced_animals: number;
        paddocks: number;
        total_capacity: number;
        total_occupancy: number;
        paddocks_needing_attention: number;
    };
    animalsBySpecies: { species: string; label: string; count: number }[];
    animalsByStatus: { status: string; label: string; count: number }[];
    paddocks: Paddock[];
    attention: Paddock[];
    recentMovements: AnimalMovement[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const isEmptyFarm = computed(
    () => props.stats.paddocks === 0 && props.animalsByStatus.every((row) => row.count === 0),
);

const largestSpeciesCount = computed(() =>
    Math.max(1, ...props.animalsBySpecies.map((row) => row.count)),
);

const farmUsagePercent = computed(() =>
    props.stats.total_capacity > 0
        ? Math.round((props.stats.total_occupancy / props.stats.total_capacity) * 100)
        : 0,
);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Dashboard"
            description="How the farm looks right now."
        />

        <EmptyState
            v-if="isEmptyFarm"
            title="Welcome to your farm"
            description="Start by creating a paddock, then add animals to it. Run `php artisan db:seed` for a demo farm."
        >
            <div class="flex gap-2">
                <Button as-child>
                    <Link :href="createPaddock()"><Plus /> Add paddock</Link>
                </Button>
                <Button as-child variant="outline">
                    <Link :href="createAnimal()"><Plus /> Add animal</Link>
                </Button>
            </div>
        </EmptyState>

        <template v-else>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card class="gap-2 py-5">
                    <CardHeader class="px-5">
                        <CardTitle class="text-muted-foreground flex items-center gap-2 text-sm font-medium">
                            <PawPrint class="size-4" /> Active animals
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="px-5">
                        <p class="text-3xl font-semibold tabular-nums">
                            {{ stats.active_animals }}
                        </p>
                        <p class="text-muted-foreground text-xs">
                            <template v-if="stats.unplaced_animals > 0">
                                {{ stats.unplaced_animals }} not in a paddock
                            </template>
                            <template v-else>all placed in paddocks</template>
                        </p>
                    </CardContent>
                </Card>

                <Card class="gap-2 py-5">
                    <CardHeader class="px-5">
                        <CardTitle class="text-muted-foreground flex items-center gap-2 text-sm font-medium">
                            <Fence class="size-4" /> Paddocks
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="px-5">
                        <p class="text-3xl font-semibold tabular-nums">
                            {{ stats.paddocks }}
                        </p>
                        <p class="text-muted-foreground text-xs">
                            {{ stats.total_occupancy }} of {{ stats.total_capacity }} places used ({{ farmUsagePercent }}%)
                        </p>
                    </CardContent>
                </Card>

                <Card
                    class="gap-2 py-5"
                    :class="{ 'border-amber-400/70': stats.paddocks_needing_attention > 0 }"
                >
                    <CardHeader class="px-5">
                        <CardTitle class="text-muted-foreground flex items-center gap-2 text-sm font-medium">
                            <AlertTriangle class="size-4" /> Needing attention
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="px-5">
                        <p class="text-3xl font-semibold tabular-nums">
                            {{ stats.paddocks_needing_attention }}
                        </p>
                        <p class="text-muted-foreground text-xs">
                            paddocks at 80% capacity or more
                        </p>
                    </CardContent>
                </Card>

                <Card class="gap-2 py-5">
                    <CardHeader class="px-5">
                        <CardTitle class="text-muted-foreground text-sm font-medium">
                            By status
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="px-5">
                        <dl class="grid grid-cols-3 gap-2 text-center">
                            <div v-for="row in animalsByStatus" :key="row.status">
                                <dt class="text-muted-foreground text-xs">
                                    {{ row.label }}
                                </dt>
                                <dd class="text-xl font-semibold tabular-nums">
                                    {{ row.count }}
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader class="flex items-center justify-between">
                        <CardTitle>Paddock occupancy</CardTitle>
                        <Button as-child variant="ghost" size="sm">
                            <Link :href="paddocksIndex()">All paddocks <ArrowRight /></Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <EmptyState
                            v-if="paddocks.length === 0"
                            title="No paddocks yet"
                        >
                            <Button as-child variant="outline">
                                <Link :href="createPaddock()">Add paddock</Link>
                            </Button>
                        </EmptyState>
                        <ul v-else class="flex flex-col gap-4">
                            <li
                                v-for="paddock in paddocks"
                                :key="paddock.id"
                                class="flex flex-col gap-1.5"
                            >
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <Link
                                        :href="showPaddock(paddock)"
                                        class="font-medium underline-offset-4 hover:underline"
                                    >
                                        {{ paddock.name }}
                                    </Link>
                                    <OccupancyBadge :state="paddock.occupancy_state" />
                                </div>
                                <OccupancyBar
                                    :occupancy="paddock.occupancy"
                                    :capacity="paddock.capacity"
                                    :state="paddock.occupancy_state"
                                />
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <div class="flex flex-col gap-4">
                    <Card
                        :class="{ 'border-amber-400/70': attention.length > 0 }"
                    >
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <AlertTriangle class="size-4 text-amber-600" />
                                Capacity warnings
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p
                                v-if="attention.length === 0"
                                class="text-muted-foreground text-sm"
                            >
                                Every paddock has room to spare.
                            </p>
                            <ul v-else class="flex flex-col gap-3 text-sm">
                                <li
                                    v-for="paddock in attention"
                                    :key="paddock.id"
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div>
                                        <Link
                                            :href="showPaddock(paddock)"
                                            class="font-medium underline-offset-4 hover:underline"
                                        >
                                            {{ paddock.name }}
                                        </Link>
                                        <p class="text-muted-foreground text-xs">
                                            <template v-if="paddock.occupancy_state === 'full'">
                                                Full — {{ paddock.occupancy }} / {{ paddock.capacity }}
                                            </template>
                                            <template v-else>
                                                {{ paddock.remaining_capacity }}
                                                place{{ paddock.remaining_capacity === 1 ? '' : 's' }}
                                                left of {{ paddock.capacity }}
                                            </template>
                                        </p>
                                    </div>
                                    <span class="font-semibold tabular-nums">
                                        {{ Math.round(paddock.occupancy_ratio * 100) }}%
                                    </span>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Animals by species</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p
                                v-if="animalsBySpecies.length === 0"
                                class="text-muted-foreground text-sm"
                            >
                                No active animals yet.
                            </p>
                            <ul v-else class="flex flex-col gap-2 text-sm">
                                <li
                                    v-for="row in animalsBySpecies"
                                    :key="row.species"
                                    class="grid grid-cols-[5rem_1fr_2rem] items-center gap-2"
                                >
                                    <Link
                                        :href="animalsIndex({ query: { species: row.species } })"
                                        class="truncate underline-offset-4 hover:underline"
                                    >
                                        {{ row.label }}
                                    </Link>
                                    <div class="bg-muted h-2 overflow-hidden rounded-full">
                                        <div
                                            class="bg-primary h-full rounded-full"
                                            :style="{ width: `${(row.count / largestSpeciesCount) * 100}%` }"
                                        />
                                    </div>
                                    <span class="text-right tabular-nums">{{ row.count }}</span>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Card>
                <CardHeader class="flex items-center justify-between">
                    <CardTitle>Recent movements</CardTitle>
                    <Button as-child variant="ghost" size="sm">
                        <Link :href="animalsIndex()">All animals <ArrowRight /></Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="recentMovements.length === 0"
                        class="text-muted-foreground text-sm"
                    >
                        No animals have been moved yet.
                    </p>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-muted-foreground border-b text-left">
                                    <th class="py-2 pr-4 font-medium">When</th>
                                    <th class="py-2 pr-4 font-medium">Animal</th>
                                    <th class="py-2 pr-4 font-medium">From</th>
                                    <th class="py-2 pr-4 font-medium">To</th>
                                    <th class="py-2 font-medium">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="movement in recentMovements"
                                    :key="movement.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="text-muted-foreground py-2 pr-4 whitespace-nowrap tabular-nums">
                                        {{ movement.moved_at_label }}
                                    </td>
                                    <td class="py-2 pr-4">
                                        <Link
                                            v-if="movement.animal"
                                            :href="showAnimal(movement.animal)"
                                            class="font-medium underline-offset-4 hover:underline"
                                        >
                                            {{ movement.animal.display_name }}
                                        </Link>
                                    </td>
                                    <td class="py-2 pr-4" :class="{ 'text-muted-foreground italic': !movement.from_paddock }">
                                        {{ movement.from_paddock?.name ?? 'Unplaced' }}
                                    </td>
                                    <td class="py-2 pr-4" :class="{ 'text-muted-foreground italic': !movement.to_paddock }">
                                        {{ movement.to_paddock?.name ?? 'Unplaced' }}
                                    </td>
                                    <td class="text-muted-foreground max-w-xs truncate py-2">
                                        {{ movement.notes ?? '' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </template>
    </div>
</template>
