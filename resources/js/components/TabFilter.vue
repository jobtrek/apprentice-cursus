<script setup lang="ts" generic="T extends string | number">
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';

defineProps<{
    options: readonly { readonly label: string; readonly value: T }[];
    /** Nom accessible du groupe, ex. « Filtrer par filière ». */
    label?: string;
}>();

const modelValue = defineModel<T>({ required: true });
</script>

<template>
    <Tabs v-model="modelValue">
        <TabsList :aria-label="label">
            <TabsTrigger
                v-for="option in options"
                :key="String(option.value)"
                :value="option.value"
                class="px-3"
            >
                {{ option.label }}
            </TabsTrigger>
        </TabsList>
    </Tabs>
</template>
