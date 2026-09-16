<script setup lang="ts">
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import type { Apprentice } from "@/composables/useApprentices";

const year = defineModel<"All" | Apprentice["year"]>({ required: true });

const options: Array<"All" | Apprentice["year"]> = [
    "All",
    "1ère",
    "2ème",
    "3ème",
    "4ème",
];


</script>

<template>
    <Tabs v-model="year" class="w-full mb-8">
        <TabsList class="flex justify-start gap-6 bg-transparent p-0 h-auto">
            <TabsTrigger
                v-for="option in options"
                :key="option"
                :value="option"
            >
                {{ option }}
            </TabsTrigger>
        </TabsList>
    </Tabs>
</template>
