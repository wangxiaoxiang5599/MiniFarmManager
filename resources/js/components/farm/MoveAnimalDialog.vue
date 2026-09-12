<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ArrowRightLeft } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AnimalMovementController from '@/actions/App/Http/Controllers/AnimalMovementController';
import FormSelect from '@/components/farm/FormSelect.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Animal, Paddock, SelectOption } from '@/types';

const props = defineProps<{
    animal: Animal;
    paddocks: Paddock[];
}>();

const open = ref(false);

const form = useForm({
    to_paddock_id: '',
    moved_at: '',
    notes: '',
});

/**
 * The current paddock and any full paddock are shown but cannot be chosen,
 * so the reason a move is impossible is visible rather than hidden.
 */
const paddockOptions = computed<SelectOption[]>(() =>
    props.paddocks.map((paddock) => {
        const isCurrent = paddock.id === props.animal.current_paddock?.id;
        const suffix = isCurrent
            ? ' — current'
            : paddock.has_room
              ? ` (${paddock.occupancy} / ${paddock.capacity})`
              : ' — full';

        return {
            value: String(paddock.id),
            label: `${paddock.name}${suffix}`,
            disabled: isCurrent || !paddock.has_room,
        };
    }),
);

const hasDestination = computed(() =>
    paddockOptions.value.some((option) => !option.disabled),
);

watch(open, (isOpen) => {
    if (!isOpen) {
        form.reset();
        form.clearErrors();
    }
});

function submit(): void {
    form.transform((data) => ({
        ...data,
        moved_at: data.moved_at === '' ? null : data.moved_at,
        notes: data.notes === '' ? null : data.notes,
    })).submit(AnimalMovementController.store(props.animal), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button :disabled="!animal.is_active">
                <ArrowRightLeft />
                {{ animal.current_paddock ? 'Move' : 'Place in paddock' }}
            </Button>
        </DialogTrigger>
        <DialogContent>
            <form class="space-y-6" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>Move {{ animal.display_name }}</DialogTitle>
                    <DialogDescription>
                        <template v-if="animal.current_paddock">
                            Currently in {{ animal.current_paddock.name }}.
                        </template>
                        <template v-else>Currently unplaced.</template>
                        Choose the paddock it is going to.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="to_paddock_id">Destination</Label>
                    <FormSelect
                        id="to_paddock_id"
                        v-model="form.to_paddock_id"
                        :options="paddockOptions"
                        placeholder="Choose a paddock"
                    />
                    <p
                        v-if="!hasDestination"
                        class="text-sm text-amber-700 dark:text-amber-300"
                    >
                        No paddock has room right now.
                    </p>
                    <InputError :message="form.errors.to_paddock_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="moved_at">
                        When
                        <span class="text-muted-foreground font-normal">
                            (optional, defaults to now)
                        </span>
                    </Label>
                    <Input
                        id="moved_at"
                        v-model="form.moved_at"
                        type="datetime-local"
                    />
                    <InputError :message="form.errors.moved_at" />
                </div>

                <div class="grid gap-2">
                    <Label for="move_notes">
                        Notes
                        <span class="text-muted-foreground font-normal"
                            >(optional)</span
                        >
                    </Label>
                    <Textarea
                        id="move_notes"
                        v-model="form.notes"
                        placeholder="Reason for the move…"
                        class="min-h-16"
                    />
                    <InputError :message="form.errors.notes" />
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="open = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing || form.to_paddock_id === ''"
                    >
                        Confirm move
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
