<script setup lang="ts">
import {AccordionItem, AccordionTrigger, AccordionContent} from "@/components/ui/accordion";
import GradeListContainer from "@/components/gradeList/GradeListContainer.vue";
import GradeListElement from "@/components/gradeList/GradeListElement.vue";
import type { Grade } from "@/types/grade";

const props = defineProps<{
    title: string,
    columns: string[],
    grades: Grade[],
}>()
</script>

<template>
    <AccordionItem :value="title">
        <AccordionTrigger>{{ title }}</AccordionTrigger>
        <AccordionContent>
            <GradeListContainer class="p-2 bg-white" :columns="columns">
                <GradeListElement
                    v-for="(grade, id) in grades"
                    :key="id"
                    v-bind="grade"
                />
            </GradeListContainer>
        </AccordionContent>
    </AccordionItem>
</template>

<style scoped>

</style>
