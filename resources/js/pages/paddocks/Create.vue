<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PaddockController from '@/actions/App/Http/Controllers/PaddockController';
import PaddockForm from '@/components/farm/PaddockForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/paddocks';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Paddocks', href: index() },
            { title: 'Add paddock', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="Add paddock" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Add paddock"
            description="Give it a name and the maximum number of animals it can hold."
        />

        <div class="max-w-xl">
            <PaddockForm
                :action="PaddockController.store.form()"
                submit-label="Create paddock"
            >
                <template #secondary>
                    <Button as-child variant="ghost">
                        <Link :href="index()">Cancel</Link>
                    </Button>
                </template>
            </PaddockForm>
        </div>
    </div>
</template>
