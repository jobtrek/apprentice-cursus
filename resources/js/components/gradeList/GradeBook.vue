<script setup lang="ts">
import { computed, ref } from 'vue';
import DomainCards from '@/components/DomainCards.vue';
import GradeAccordionItem from '@/components/gradeList/GradeAccordionItem.vue';
import { SectionHeader } from '@/components/page';
import SearchInput from '@/components/SearchInput.vue';
import { Accordion } from '@/components/ui/accordion';
import { DOMAIN_GRADES, FINAL_GRADE, GRADE_TABLES } from '@/data/gradebook';
import type { Grade, GradeMenu } from '@/types/grade';
import type { RouteDefinition } from '@/wayfinder';

const props = defineProps<{
    /** Page de détail d'une note : diffère entre l'apprenti et le coach. */
    gradeHref: (grade: Grade) => RouteDefinition<'get'>;
}>();

const search = ref('');

/** Filtre les notes de chaque nœud sans masquer les domaines eux-mêmes. */
function filterMenu(menu: GradeMenu, query: string): GradeMenu {
    return {
        ...menu,
        grades: menu.grades.filter((grade) =>
            `${grade.title} ${grade.subject}`.toLowerCase().includes(query),
        ),
        subMenu: menu.subMenu?.map((sub) => filterMenu(sub, query)),
    };
}

const filteredTables = computed(() => {
    const query = search.value.trim().toLowerCase();

    return query
        ? GRADE_TABLES.map((menu) => filterMenu(menu, query))
        : GRADE_TABLES;
});
</script>

<template>
    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <DomainCards
            class="sm:col-span-2 lg:col-span-4"
            :title="FINAL_GRADE.title"
            :grade="FINAL_GRADE.grade"
            :weight="FINAL_GRADE.weight"
        />
        <DomainCards
            v-for="domain in DOMAIN_GRADES"
            :key="domain.title"
            :title="domain.title"
            :grade="domain.grade"
            :weight="domain.weight"
        />
    </section>

    <section class="flex flex-col gap-4">
        <SectionHeader title="Notes">
            <template #actions>
                <SearchInput
                    v-model="search"
                    placeholder="Rechercher une note"
                    class="w-full sm:w-64"
                />
            </template>
        </SectionHeader>

        <Accordion type="multiple">
            <GradeAccordionItem
                v-for="table in filteredTables"
                :key="table.title"
                :menu="table"
                :grade-href="props.gradeHref"
            />
        </Accordion>
    </section>
</template>
