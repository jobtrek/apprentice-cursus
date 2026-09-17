<script setup lang="ts">
import { ref } from "vue";
import type { HTMLAttributes } from "vue";
import { cn } from "@/lib/utils";
import GradeListContainer from "@/components/gradeList/GradeListContainer.vue";
import GradeListElement from "@/components/gradeList/GradeListElement.vue";
import ApprenticeSearchBar from "@/components/apprentice/ApprenticeSearchBar.vue";
import type { Grade } from "@/types/grade";

const props = defineProps<{
    class?: HTMLAttributes["class"],
    tables: { title: string, columns: string[], grades: Grade[] }[]
}>()

const search = ref<string>('')
</script>

<template>
    <section :class="cn('grid grid-rows-[auto_1fr] h-fit mt-5', props.class)">
        <div class="flex flex-row self-start items-center justify-between px-2">
            <h2 class="m-0 flex items-center text-lg font-semibold">Notes</h2>
            <ApprenticeSearchBar v-model="search" class="w-fit mb-0" />
        </div>
        <div class="flex flex-col">
            <section v-for="table in tables" :key="table.title" class="flex flex-col">
                <h2>{{ table.title }}</h2>
                <GradeListContainer class="bg-white" :columns="table.columns">
                    <GradeListElement
                        v-for="(grade, id) in table.grades"
                        :key="id"
                        v-bind="grade"
                    />
                </GradeListContainer>
            </section>
        </div>
    </section>
</template>

<style scoped>

</style>
