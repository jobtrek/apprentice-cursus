<script setup lang="ts" generic="T extends string | number">
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";

defineProps<{
    options: readonly { readonly label: string; readonly value: T }[];
}>();

const modelValue = defineModel<T>({ required: true });
</script>

<template>
    <Tabs v-model="modelValue" class="mb-6 w-full">
        <TabsList
            class="grid h-12 w-full rounded-xl border bg-muted"
            :style="{
                gridTemplateColumns: `repeat(${options.length}, minmax(0, 1fr))`,
            }"
        >
            <TabsTrigger
                v-for="option in options"
                :key="String(option.value)"
                :value="option.value"
                class="rounded-lg text-base"
            >
                {{ option.label }}
            </TabsTrigger>
        </TabsList>
    </Tabs>
</template>
