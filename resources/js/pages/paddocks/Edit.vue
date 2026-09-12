<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import PaddockController from '@/actions/App/Http/Controllers/PaddockController';
import PaddockForm from '@/components/farm/PaddockForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { edit, index, show } from '@/routes/paddocks';
import type { Paddock } from '@/types';

const props = defineProps<{
    paddock: Paddock;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Paddocks', href: index() },
        { title: props.paddock.name, href: show(props.paddock) },
        { title: 'Edit', href: edit(props.paddock) },
    ],
});
</script>

<template>
    <Head :title="`Edit ${props.paddock.name}`" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            :title="`Edit ${props.paddock.name}`"
            description="Rename the paddock or change how many animals it can hold."
        />

        <div class="max-w-xl">
            <PaddockForm
                :action="PaddockController.update.form(props.paddock)"
                :paddock="props.paddock"
                submit-label="Save changes"
            >
                <template #secondary>
                    <Button as-child variant="ghost">
                        <Link :href="show(props.paddock)">Cancel</Link>
                    </Button>
                </template>
            </PaddockForm>
        </div>
    </div>
</template>
