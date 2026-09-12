<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Paddock } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

defineProps<{
    /** Wayfinder form definition, e.g. PaddockController.store.form() */
    action: RouteFormDefinition<'post' | 'put'>;
    paddock?: Paddock;
    submitLabel: string;
}>();
</script>

<template>
    <Form v-bind="action" class="space-y-6" v-slot="{ errors, processing }">
        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
                id="name"
                name="name"
                :default-value="paddock?.name"
                required
                autofocus
                placeholder="e.g. North Paddock"
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="capacity">Maximum capacity</Label>
            <Input
                id="capacity"
                name="capacity"
                type="number"
                min="1"
                :default-value="paddock?.capacity"
                required
                class="max-w-40"
            />
            <p
                v-if="paddock && paddock.occupancy > 0"
                class="text-muted-foreground text-xs"
            >
                {{ paddock.occupancy }} animals are currently here, so capacity
                cannot go below that.
            </p>
            <InputError :message="errors.capacity" />
        </div>

        <div class="grid gap-2">
            <Label for="notes">Notes</Label>
            <Textarea
                id="notes"
                name="notes"
                :default-value="paddock?.notes ?? ''"
                placeholder="Water access, fencing, pasture type…"
            />
            <InputError :message="errors.notes" />
        </div>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="processing">
                {{ submitLabel }}
            </Button>
            <slot name="secondary" />
        </div>
    </Form>
</template>
