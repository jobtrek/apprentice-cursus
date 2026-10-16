<script setup lang="ts" generic="T extends string">
import { Tabs, TabsList } from '@/components/ui/tabs';

const tab = defineModel<T>({ required: true });
</script>

<template>
    <Tabs v-model="tab" class="gap-0 border-b px-6">
        <TabsList
            aria-label="Catégorie de la note"
            class="h-auto w-full justify-start gap-6 rounded-none bg-transparent p-0"
        >
            <slot />
        </TabsList>
    </Tabs>
</template>
