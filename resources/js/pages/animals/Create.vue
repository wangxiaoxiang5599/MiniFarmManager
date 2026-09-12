<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AnimalController from '@/actions/App/Http/Controllers/AnimalController';
import AnimalForm from '@/components/farm/AnimalForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/animals';
import type { AnimalFormOptions } from '@/types';

defineProps<{
    options: AnimalFormOptions;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Animals', href: index() },
            { title: 'Add animal', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="Add animal" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Add animal"
            description="Register a new animal and optionally place it in a paddock straight away."
        />

        <div class="max-w-2xl">
            <AnimalForm
                :route="AnimalController.store()"
                :options="options"
                submit-label="Add animal"
            >
                <template #secondary>
                    <Button as-child variant="ghost">
                        <Link :href="index()">Cancel</Link>
                    </Button>
                </template>
            </AnimalForm>
        </div>
    </div>
</template>
