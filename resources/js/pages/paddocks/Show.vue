<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import EmptyState from '@/components/farm/EmptyState.vue';
import OccupancyBadge from '@/components/farm/OccupancyBadge.vue';
import OccupancyBar from '@/components/farm/OccupancyBar.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { edit, index, show } from '@/routes/paddocks';
import type { AnimalSummary, Paddock } from '@/types';

const props = defineProps<{
    paddock: Paddock;
    animals: AnimalSummary[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Paddocks', href: index() },
        { title: props.paddock.name, href: show(props.paddock) },
    ],
});
</script>

<template>
    <Head :title="props.paddock.name" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <Heading :title="props.paddock.name" />
                <OccupancyBadge
                    :state="props.paddock.occupancy_state"
                    class="mb-8"
                />
            </div>
            <Button as-child variant="outline">
                <Link :href="edit(props.paddock)">
                    <Pencil />
                    Edit
                </Link>
            </Button>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <Card class="lg:col-span-1">
                <CardHeader>
                    <CardTitle>Capacity</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-col gap-4">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-semibold tabular-nums">
                            {{ props.paddock.occupancy }}
                        </span>
                        <span class="text-muted-foreground">
                            of {{ props.paddock.capacity }} places used
                        </span>
                    </div>
                    <OccupancyBar
                        :occupancy="props.paddock.occupancy"
                        :capacity="props.paddock.capacity"
                        :state="props.paddock.occupancy_state"
                        :show-label="false"
                    />
                    <p
                        v-if="props.paddock.occupancy_state === 'full'"
                        class="text-sm text-red-700 dark:text-red-300"
                    >
                        This paddock is full. Move an animal out before adding
                        another.
                    </p>
                    <p
                        v-else-if="props.paddock.occupancy_state === 'warning'"
                        class="text-sm text-amber-700 dark:text-amber-300"
                    >
                        Nearly full — only
                        {{ props.paddock.remaining_capacity }} place{{
                            props.paddock.remaining_capacity === 1 ? '' : 's'
                        }}
                        left.
                    </p>
                    <p
                        v-if="props.paddock.notes"
                        class="text-muted-foreground border-t pt-4 text-sm whitespace-pre-line"
                    >
                        {{ props.paddock.notes }}
                    </p>
                </CardContent>
            </Card>

            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>
                        Animals in this paddock ({{ props.animals.length }})
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <EmptyState
                        v-if="props.animals.length === 0"
                        title="No animals here"
                        description="Animals moved into this paddock will appear in this list."
                    />
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="text-muted-foreground border-b text-left"
                                >
                                    <th class="py-2 pr-4 font-medium">Tag</th>
                                    <th class="py-2 pr-4 font-medium">Name</th>
                                    <th class="py-2 pr-4 font-medium">
                                        Species
                                    </th>
                                    <th class="py-2 pr-4 font-medium">Sex</th>
                                    <th class="py-2 font-medium">Age</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="animal in props.animals"
                                    :key="animal.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-2 pr-4 font-mono">
                                        {{ animal.tag_number }}
                                    </td>
                                    <td class="py-2 pr-4">
                                        {{ animal.name ?? '—' }}
                                    </td>
                                    <td class="py-2 pr-4">
                                        {{ animal.species_label }}
                                    </td>
                                    <td class="py-2 pr-4">
                                        {{ animal.sex_label }}
                                    </td>
                                    <td class="py-2">{{ animal.age }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
