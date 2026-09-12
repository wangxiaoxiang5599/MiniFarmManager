<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import FormSelect from '@/components/farm/FormSelect.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Animal, AnimalFormOptions, SelectOption } from '@/types';
import type { RouteDefinition } from '@/wayfinder';

const props = defineProps<{
    /** Wayfinder route, e.g. AnimalController.store() */
    route: RouteDefinition<'post' | 'put'>;
    options: AnimalFormOptions;
    animal?: Animal;
    submitLabel: string;
}>();

const isEditing = computed(() => props.animal !== undefined);

const form = useForm({
    tag_number: props.animal?.tag_number ?? '',
    name: props.animal?.name ?? '',
    species: props.animal?.species ?? '',
    sex: props.animal?.sex ?? '',
    date_of_birth: props.animal?.date_of_birth ?? '',
    breed: props.animal?.breed ?? '',
    status: props.animal?.status ?? 'active',
    notes: props.animal?.notes ?? '',
    paddock_id: '',
});

const today = new Date().toISOString().slice(0, 10);

/**
 * Paddock choices for the initial placement. Full paddocks are listed but
 * disabled so the user understands why they cannot be chosen.
 */
const paddockOptions = computed<SelectOption[]>(() =>
    props.options.paddocks.map((paddock) => ({
        value: String(paddock.id),
        label: paddock.has_room
            ? `${paddock.name} (${paddock.occupancy} / ${paddock.capacity})`
            : `${paddock.name} — full`,
        disabled: !paddock.has_room,
    })),
);

const leavingActive = computed(
    () =>
        isEditing.value &&
        props.animal?.status === 'active' &&
        form.status !== 'active' &&
        props.animal?.current_paddock,
);

const returningToActive = computed(
    () =>
        isEditing.value &&
        props.animal?.status !== 'active' &&
        form.status === 'active',
);

function submit(): void {
    form.transform((data) => {
        const payload: Record<string, unknown> = { ...data };

        if (isEditing.value) {
            delete payload.paddock_id;
        } else {
            delete payload.status;
            if (payload.paddock_id === '') {
                payload.paddock_id = null;
            }
        }

        return payload;
    }).submit(props.route);
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-6 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="tag_number">Tag number</Label>
                <Input
                    id="tag_number"
                    v-model="form.tag_number"
                    required
                    autofocus
                    placeholder="e.g. NZ-0042"
                    class="font-mono"
                />
                <InputError :message="form.errors.tag_number" />
            </div>

            <div class="grid gap-2">
                <Label for="name"
                    >Name
                    <span class="text-muted-foreground font-normal"
                        >(optional)</span
                    ></Label
                >
                <Input id="name" v-model="form.name" placeholder="e.g. Daisy" />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="species">Species</Label>
                <FormSelect
                    id="species"
                    v-model="form.species"
                    :options="options.species"
                    placeholder="Choose species"
                />
                <InputError :message="form.errors.species" />
            </div>

            <div class="grid gap-2">
                <Label for="sex">Sex</Label>
                <FormSelect
                    id="sex"
                    v-model="form.sex"
                    :options="options.sexes"
                    placeholder="Choose sex"
                />
                <InputError :message="form.errors.sex" />
            </div>

            <div class="grid gap-2">
                <Label for="date_of_birth">Date of birth</Label>
                <Input
                    id="date_of_birth"
                    v-model="form.date_of_birth"
                    type="date"
                    :max="today"
                    required
                />
                <InputError :message="form.errors.date_of_birth" />
            </div>

            <div class="grid gap-2">
                <Label for="breed"
                    >Breed
                    <span class="text-muted-foreground font-normal"
                        >(optional)</span
                    ></Label
                >
                <Input
                    id="breed"
                    v-model="form.breed"
                    placeholder="e.g. Angus"
                />
                <InputError :message="form.errors.breed" />
            </div>

            <div v-if="!isEditing" class="grid gap-2 sm:col-span-2">
                <Label for="paddock_id"
                    >Place in paddock
                    <span class="text-muted-foreground font-normal"
                        >(optional)</span
                    ></Label
                >
                <FormSelect
                    id="paddock_id"
                    v-model="form.paddock_id"
                    :options="paddockOptions"
                    all-label="Leave unplaced for now"
                    placeholder="Leave unplaced for now"
                />
                <InputError :message="form.errors.paddock_id" />
            </div>

            <div v-else class="grid gap-2 sm:col-span-2">
                <Label for="status">Status</Label>
                <FormSelect
                    id="status"
                    v-model="form.status"
                    :options="options.statuses"
                />
                <p
                    v-if="leavingActive"
                    class="text-sm text-amber-700 dark:text-amber-300"
                >
                    Saving will move {{ animal?.tag_number }} out of
                    {{ animal?.current_paddock?.name }} and free up its place.
                </p>
                <p
                    v-else-if="returningToActive"
                    class="text-muted-foreground text-sm"
                >
                    The animal will become active but stay unplaced — move it
                    into a paddock from its detail page.
                </p>
                <InputError :message="form.errors.status" />
            </div>

            <div class="grid gap-2 sm:col-span-2">
                <Label for="notes"
                    >Notes
                    <span class="text-muted-foreground font-normal"
                        >(optional)</span
                    ></Label
                >
                <Textarea
                    id="notes"
                    v-model="form.notes"
                    placeholder="Temperament, markings, purchase details…"
                />
                <InputError :message="form.errors.notes" />
            </div>
        </div>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">
                {{ submitLabel }}
            </Button>
            <slot name="secondary" />
        </div>
    </form>
</template>
