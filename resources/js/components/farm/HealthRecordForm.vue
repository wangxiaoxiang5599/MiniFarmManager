<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import HealthRecordController from '@/actions/App/Http/Controllers/HealthRecordController';
import FormSelect from '@/components/farm/FormSelect.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Animal, SelectOption } from '@/types';

const props = defineProps<{
    animal: Animal;
    types: SelectOption[];
}>();

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    recorded_on: today,
    type: '',
    description: '',
    notes: '',
});

function submit(): void {
    form.transform((data) => ({
        ...data,
        notes: data.notes === '' ? null : data.notes,
    })).submit(HealthRecordController.store(props.animal), {
        preserveScroll: true,
        onSuccess: () => form.reset('type', 'description', 'notes'),
    });
}
</script>

<template>
    <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="recorded_on">Date</Label>
            <Input
                id="recorded_on"
                v-model="form.recorded_on"
                type="date"
                :max="today"
                required
            />
            <InputError :message="form.errors.recorded_on" />
        </div>

        <div class="grid gap-2">
            <Label for="type">Type</Label>
            <FormSelect
                id="type"
                v-model="form.type"
                :options="types"
                placeholder="Choose type"
            />
            <InputError :message="form.errors.type" />
        </div>

        <div class="grid gap-2 sm:col-span-2">
            <Label for="description">Description</Label>
            <Input
                id="description"
                v-model="form.description"
                required
                placeholder="e.g. 5-in-1 vaccine, second dose"
            />
            <InputError :message="form.errors.description" />
        </div>

        <div class="grid gap-2 sm:col-span-2">
            <Label for="health_notes">
                Notes
                <span class="text-muted-foreground font-normal"
                    >(optional)</span
                >
            </Label>
            <Textarea
                id="health_notes"
                v-model="form.notes"
                placeholder="Dosage, vet, follow-up…"
                class="min-h-16"
            />
            <InputError :message="form.errors.notes" />
        </div>

        <div class="sm:col-span-2">
            <Button type="submit" :disabled="form.processing">
                Add record
            </Button>
        </div>
    </form>
</template>
