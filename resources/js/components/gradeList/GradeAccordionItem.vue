<script setup lang="ts">
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import DataTable from '@/components/DataTable.vue';
import GradeListElement from '@/components/gradeList/GradeListElement.vue';
import { cn } from '@/lib/utils';
import type { GradeMenu } from '@/types/grade';

withDefaults(
    defineProps<{
        menu: GradeMenu;
        depth?: number;
    }>(),
    {
        depth: 0,
    },
);
</script>

<template>
    <AccordionItem :value="menu.title">
        <AccordionTrigger
            :class="
                cn(
                    'flex-row-reverse justify-end gap-2 hover:no-underline',
                    depth === 0 ? 'text-base font-semibold' : 'text-sm',
                )
            "
        >
            {{ menu.title }}
        </AccordionTrigger>
        <AccordionContent>
            <Accordion
                v-if="menu.subMenu"
                type="multiple"
                class="ml-2 border-l pl-4"
            >
                <GradeAccordionItem
                    v-for="sub in menu.subMenu"
                    :key="sub.title"
                    :menu="sub"
                    :depth="depth + 1"
                />
            </Accordion>
            <DataTable
                v-else
                :columns="menu.columns"
                :data="menu.grades"
                empty-message="Aucune note trouvée."
            >
                <template #row="{ item }">
                    <GradeListElement v-bind="item" />
                </template>
            </DataTable>
        </AccordionContent>
    </AccordionItem>
</template>
