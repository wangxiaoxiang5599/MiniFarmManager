<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { ArrowRight, Pencil } from '@lucide/vue';
import AnimalStatusBadge from '@/components/farm/AnimalStatusBadge.vue';
import EmptyState from '@/components/farm/EmptyState.vue';
import HealthRecordForm from '@/components/farm/HealthRecordForm.vue';
import MoveAnimalDialog from '@/components/farm/MoveAnimalDialog.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { edit, index, show } from '@/routes/animals';
import { show as showPaddock } from '@/routes/paddocks';
import type {
    Animal,
    AnimalMovement,
    HealthRecord,
    Paddock,
    SelectOption,
} from '@/types';

const props = defineProps<{
    animal: Animal;
    movements: AnimalMovement[];
    healthRecords: HealthRecord[];
    paddocks: Paddock[];
    healthRecordTypes: SelectOption[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Animals', href: index() },
        { title: props.animal.display_name, href: show(props.animal) },
    ],
});

const details = [
    { label: 'Species', value: props.animal.species_label },
    { label: 'Sex', value: props.animal.sex_label },
    { label: 'Breed', value: props.animal.breed ?? '—' },
    {
        label: 'Date of birth',
        value: `${props.animal.date_of_birth} (${props.animal.age})`,
    },
];
</script>

<template>
    <Head :title="props.animal.display_name" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <Heading :title="props.animal.display_name" />
                <AnimalStatusBadge
                    :status="props.animal.status"
                    :label="props.animal.status_label"
                    class="mb-8"
                />
            </div>
            <Button as-child variant="outline">
                <Link :href="edit(props.animal)">
                    <Pencil />
                    Edit
                </Link>
            </Button>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="flex flex-col gap-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Current paddock</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-3">
                        <Link
                            v-if="props.animal.current_paddock"
                            :href="showPaddock(props.animal.current_paddock)"
                            class="text-2xl font-semibold underline-offset-4 hover:underline"
                        >
                            {{ props.animal.current_paddock.name }}
                        </Link>
                        <p
                            v-else
                            class="text-muted-foreground text-2xl font-semibold"
                        >
                            Unplaced
                        </p>
                        <p
                            v-if="!props.animal.is_active"
                            class="text-muted-foreground text-sm"
                        >
                            {{ props.animal.status_label }} animals do not
                            occupy a paddock.
                        </p>
                        <div v-else>
                            <MoveAnimalDialog
                                :animal="props.animal"
                                :paddocks="props.paddocks"
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl
                            class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm"
                        >
                            <template v-for="item in details" :key="item.label">
                                <dt class="text-muted-foreground">
                                    {{ item.label }}
                                </dt>
                                <dd>{{ item.value }}</dd>
                            </template>
                        </dl>
                        <p
                            v-if="props.animal.notes"
                            class="text-muted-foreground mt-4 border-t pt-4 text-sm whitespace-pre-line"
                        >
                            {{ props.animal.notes }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="flex flex-col gap-4 lg:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Movement history</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <EmptyState
                            v-if="props.movements.length === 0"
                            title="No movements recorded"
                            description="This animal has never been placed in a paddock."
                        />
                        <ol v-else class="divide-y">
                            <li
                                v-for="movement in props.movements"
                                :key="movement.id"
                                class="flex flex-col gap-1 py-3 text-sm first:pt-0 last:pb-0"
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="text-muted-foreground"
                                        :class="{
                                            italic: !movement.from_paddock,
                                        }"
                                    >
                                        {{
                                            movement.from_paddock?.name ??
                                            'Unplaced'
                                        }}
                                    </span>
                                    <ArrowRight
                                        class="text-muted-foreground size-4"
                                    />
                                    <span
                                        class="font-medium"
                                        :class="{
                                            'text-muted-foreground italic':
                                                !movement.to_paddock,
                                        }"
                                    >
                                        {{
                                            movement.to_paddock?.name ??
                                            'Unplaced'
                                        }}
                                    </span>
                                    <span
                                        class="text-muted-foreground ml-auto text-xs tabular-nums"
                                    >
                                        {{ movement.moved_at_label }}
                                    </span>
                                </div>
                                <p
                                    v-if="movement.notes"
                                    class="text-muted-foreground text-xs"
                                >
                                    {{ movement.notes }}
                                </p>
                            </li>
                        </ol>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Health history</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-6">
                        <HealthRecordForm
                            :animal="props.animal"
                            :types="props.healthRecordTypes"
                        />

                        <EmptyState
                            v-if="props.healthRecords.length === 0"
                            title="No health records yet"
                            description="Vaccinations, treatments, injuries and check-ups will be listed here."
                        />
                        <ol v-else class="divide-y border-t">
                            <li
                                v-for="record in props.healthRecords"
                                :key="record.id"
                                class="flex flex-col gap-1 py-3 text-sm last:pb-0"
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge variant="secondary">
                                        {{ record.type_label }}
                                    </Badge>
                                    <span class="font-medium">
                                        {{ record.description }}
                                    </span>
                                    <span
                                        class="text-muted-foreground ml-auto text-xs tabular-nums"
                                    >
                                        {{ record.recorded_on_label }}
                                    </span>
                                </div>
                                <p
                                    v-if="record.notes"
                                    class="text-muted-foreground text-xs whitespace-pre-line"
                                >
                                    {{ record.notes }}
                                </p>
                            </li>
                        </ol>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
