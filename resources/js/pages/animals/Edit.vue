<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import AnimalController from '@/actions/App/Http/Controllers/AnimalController';
import AnimalForm from '@/components/farm/AnimalForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { edit, index, show } from '@/routes/animals';
import type { Animal, AnimalFormOptions } from '@/types';

const props = defineProps<{
    animal: Animal;
    options: AnimalFormOptions;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Animals', href: index() },
        { title: props.animal.display_name, href: show(props.animal) },
        { title: 'Edit', href: edit(props.animal) },
    ],
});
</script>

<template>
    <Head :title="`Edit ${props.animal.display_name}`" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            :title="`Edit ${props.animal.display_name}`"
            description="Update the animal's details or change its status."
        />

        <div class="max-w-2xl">
            <AnimalForm
                :route="AnimalController.update(props.animal)"
                :options="props.options"
                :animal="props.animal"
                submit-label="Save changes"
            >
                <template #secondary>
                    <Button as-child variant="ghost">
                        <Link :href="show(props.animal)">Cancel</Link>
                    </Button>
                </template>
            </AnimalForm>
        </div>
    </div>
</template>
