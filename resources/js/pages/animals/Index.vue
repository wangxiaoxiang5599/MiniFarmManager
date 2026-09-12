<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, reactive, watch } from 'vue';
import AnimalStatusBadge from '@/components/farm/AnimalStatusBadge.vue';
import EmptyState from '@/components/farm/EmptyState.vue';
import FormSelect from '@/components/farm/FormSelect.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, index, show } from '@/routes/animals';
import { show as showPaddock } from '@/routes/paddocks';
import type {
    AnimalFormOptions,
    AnimalSummary,
    Paginated,
    SelectOption,
} from '@/types';

const props = defineProps<{
    animals: Paginated<AnimalSummary>;
    filters: {
        search: string;
        species: string;
        status: string;
        paddock: string | number;
    };
    options: AnimalFormOptions;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Animals', href: index() }],
    },
});

const filters = reactive({
    search: props.filters.search,
    species: props.filters.species,
    status: props.filters.status,
    paddock: String(props.filters.paddock ?? ''),
});

const paddockOptions = computed<SelectOption[]>(() =>
    props.options.paddocks.map((paddock) => ({
        value: String(paddock.id),
        label: paddock.name,
    })),
);

const hasFilters = computed(() =>
    Object.values(filters).some((value) => value !== ''),
);

function applyFilters(): void {
    const query = Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== ''),
    );

    router.get(index(), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['animals', 'filters'],
    });
}

function clearFilters(): void {
    filters.search = '';
    filters.species = '';
    filters.status = '';
    filters.paddock = '';
}

watchDebounced(() => filters.search, applyFilters, { debounce: 300 });
watch(() => [filters.species, filters.status, filters.paddock], applyFilters);
</script>

<template>
    <Head title="Animals" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Animals"
                description="Every animal on the farm, where it is and its status."
            />
            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    Add animal
                </Link>
            </Button>
        </div>

        <div
            class="grid gap-3 md:grid-cols-[minmax(0,2fr)_repeat(3,minmax(0,1fr))_auto]"
        >
            <div class="relative">
                <Search
                    class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                />
                <Input
                    v-model="filters.search"
                    placeholder="Search tag or name"
                    class="pl-9"
                    aria-label="Search animals"
                />
            </div>
            <FormSelect
                v-model="filters.species"
                :options="options.species"
                all-label="All species"
                placeholder="All species"
            />
            <FormSelect
                v-model="filters.status"
                :options="options.statuses"
                all-label="All statuses"
                placeholder="All statuses"
            />
            <FormSelect
                v-model="filters.paddock"
                :options="paddockOptions"
                all-label="All paddocks"
                placeholder="All paddocks"
            />
            <Button
                variant="ghost"
                :disabled="!hasFilters"
                @click="clearFilters"
            >
                Clear
            </Button>
        </div>

        <EmptyState
            v-if="animals.data.length === 0 && !hasFilters"
            title="No animals yet"
            description="Add your first animal to start tracking the herd."
        >
            <Button as-child variant="outline">
                <Link :href="create()">Add animal</Link>
            </Button>
        </EmptyState>

        <EmptyState
            v-else-if="animals.data.length === 0"
            title="No animals match these filters"
        >
            <Button variant="outline" @click="clearFilters">
                Clear filters
            </Button>
        </EmptyState>

        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="text-muted-foreground bg-muted/40 border-b text-left"
                    >
                        <th class="px-4 py-2.5 font-medium">Tag</th>
                        <th class="px-4 py-2.5 font-medium">Name</th>
                        <th class="px-4 py-2.5 font-medium">Species</th>
                        <th class="px-4 py-2.5 font-medium">Sex</th>
                        <th class="px-4 py-2.5 font-medium">Age</th>
                        <th class="px-4 py-2.5 font-medium">Paddock</th>
                        <th class="px-4 py-2.5 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="animal in animals.data"
                        :key="animal.id"
                        class="hover:bg-accent/40 border-b last:border-0"
                    >
                        <td class="px-4 py-2.5 font-mono">
                            <Link
                                :href="show(animal)"
                                class="font-medium underline-offset-4 hover:underline"
                            >
                                {{ animal.tag_number }}
                            </Link>
                        </td>
                        <td class="px-4 py-2.5">{{ animal.name ?? '—' }}</td>
                        <td class="px-4 py-2.5">{{ animal.species_label }}</td>
                        <td class="px-4 py-2.5">{{ animal.sex_label }}</td>
                        <td class="px-4 py-2.5">{{ animal.age }}</td>
                        <td class="px-4 py-2.5">
                            <Link
                                v-if="animal.current_paddock"
                                :href="showPaddock(animal.current_paddock)"
                                class="underline-offset-4 hover:underline"
                            >
                                {{ animal.current_paddock.name }}
                            </Link>
                            <span v-else class="text-muted-foreground">
                                Unplaced
                            </span>
                        </td>
                        <td class="px-4 py-2.5">
                            <AnimalStatusBadge
                                :status="animal.status"
                                :label="animal.status_label"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="animals.meta.last_page > 1"
            class="flex items-center justify-between text-sm"
        >
            <span class="text-muted-foreground">
                Showing {{ animals.meta.from }}–{{ animals.meta.to }} of
                {{ animals.meta.total }}
            </span>
            <div class="flex gap-2">
                <Button
                    as-child
                    variant="outline"
                    size="sm"
                    :disabled="!animals.links.prev"
                >
                    <Link
                        :href="animals.links.prev ?? '#'"
                        preserve-scroll
                        preserve-state
                    >
                        Previous
                    </Link>
                </Button>
                <Button
                    as-child
                    variant="outline"
                    size="sm"
                    :disabled="!animals.links.next"
                >
                    <Link
                        :href="animals.links.next ?? '#'"
                        preserve-scroll
                        preserve-state
                    >
                        Next
                    </Link>
                </Button>
            </div>
        </div>
    </div>
</template>
