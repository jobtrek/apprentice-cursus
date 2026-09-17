<script setup lang="ts">
import {Accordion, AccordionItem, AccordionTrigger, AccordionContent} from "@/components/ui/accordion";
import GradeListContainer from "@/components/gradeList/GradeListContainer.vue";
import GradeListElement from "@/components/gradeList/GradeListElement.vue";
import type { Grade } from "@/types/grade";

type menu = {
    title: string
    columns: string[]
    grades: Grade[]
    subMenu?: menu[]
}

const props = withDefaults(defineProps<{
    menu: menu
    depth?: number
}>(), {
    depth: 0,
})
</script>

<template>
    <AccordionItem :value="menu.title">
        <AccordionTrigger :class="depth === 0 ? 'text-lg font-semibold' : 'text-sm font-medium'">
            {{ menu.title }}
        </AccordionTrigger>
        <AccordionContent>
            <Accordion
                v-if="menu.subMenu"
                type="multiple"
                class="ml-4 border-l-2 border-muted-foreground/30 pl-4"
            >
                <GradeAccordionItem
                    v-for="sub in menu.subMenu"
                    :key="sub.title"
                    :menu="sub"
                    :depth="depth + 1"
                />
            </Accordion>
            <GradeListContainer v-else class="p-2 bg-white" :columns="menu.columns">
                <GradeListElement
                    v-for="(grade, id) in menu.grades"
                    :key="id"
                    v-bind="grade"
                />
            </GradeListContainer>
        </AccordionContent>
    </AccordionItem>
</template>

<style scoped>

</style>
