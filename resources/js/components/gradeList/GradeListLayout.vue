<script setup lang="ts">
import { ref } from "vue";
import type { HTMLAttributes } from "vue";
import { cn } from "@/lib/utils";
import ApprenticeSearchBar from "@/components/apprentice/ApprenticeSearchBar.vue";

const props = defineProps<{ class?: HTMLAttributes["class"], }>()

const search = ref<string>('')
</script>

<template>
    <section :class="cn('grid grid-rows-[auto_1fr] h-fit mt-5', props.class)">
        <div class="flex flex-row items-center justify-between">
            <h2 class="flex text-2xl font-bold">Notes</h2>
            <ApprenticeSearchBar v-model="search" class="w-fit mb-0"/>
        </div>
        <div class="flex flex-col gap-4">
            <slot />
        </div>
    </section>
</template>
