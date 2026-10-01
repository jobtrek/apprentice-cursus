<script setup lang="ts" generic="T extends string">
import { computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

const props = defineProps<{
    options: readonly { value: T; label: string }[];
    /** Nom accessible, ex. « Filtrer par année ». */
    label: string;
    /** Valeur « sans filtre » : le déclencheur reste neutre tant qu'elle est choisie. */
    neutral?: T;
    class?: string;
}>();

const model = defineModel<T>({ required: true });

const current = computed(
    () => props.options.find(({ value }) => value === model.value)?.label,
);

const isActive = computed(
    () => props.neutral !== undefined && model.value !== props.neutral,
);
</script>

<template>
    <Select v-model="model">
        <SelectTrigger
            :aria-label="label"
            :class="
                cn(
                    'w-full sm:w-auto',
                    isActive && 'border-primary text-primary',
                    props.class,
                )
            "
        >
            <slot name="icon" />
            <!-- Libellé explicite : affiché dès le rendu serveur. -->
            <SelectValue>{{ current }}</SelectValue>
        </SelectTrigger>
        <SelectContent>
            <SelectItem
                v-for="option in options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
