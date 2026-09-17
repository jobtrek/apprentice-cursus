<script setup lang="ts">
import { ref } from "vue";
import type { HTMLAttributes } from "vue";
import { cn } from "@/lib/utils";
import ApprenticeSearchBar from "@/components/apprentice/ApprenticeSearchBar.vue";
import type { Grade } from "@/types/grade";

const props = defineProps<{ class?: HTMLAttributes["class"], }>()

const search = ref<string>('')
</script>

<template>
    <section :class="cn('grid grid-rows-[auto_1fr] h-fit mt-5', props.class)">
        <div class="flex flex-row self-start items-center justify-between">
            <h2 class="m-0 flex items-center text-2xl font-bold">Notes</h2>
            <ApprenticeSearchBar v-model="search" class="w-fit mb-0" />
        </div>
        <div class="flex flex-col gap-4">
            <slot />
        </div>
    </section>
</template>

<style scoped>

</style>
