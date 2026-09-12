<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { SelectOption } from '@/types';

withDefaults(
    defineProps<{
        id?: string;
        options: SelectOption[];
        placeholder?: string;
        disabled?: boolean;
        class?: string;
        /** Adds an "All" style option that clears the selection. */
        allLabel?: string;
    }>(),
    { placeholder: 'Select…' },
);

const model = defineModel<string>({ default: '' });

/**
 * reka-ui cannot represent "no value" with an empty string, so the clearing
 * option uses a sentinel that is mapped back to '' on the way out.
 */
const ALL = '__all__';

function onUpdate(value: unknown): void {
    model.value = value === ALL ? '' : String(value ?? '');
}
</script>

<template>
    <Select
        :model-value="model === '' && allLabel ? ALL : model"
        :disabled="disabled"
        @update:model-value="onUpdate"
    >
        <SelectTrigger :id="id" :class="['w-full', $props.class]">
            <SelectValue :placeholder="placeholder" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem v-if="allLabel" :value="ALL">{{ allLabel }}</SelectItem>
            <SelectItem
                v-for="option in options"
                :key="option.value"
                :value="option.value"
                :disabled="option.disabled"
            >
                {{ option.label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
