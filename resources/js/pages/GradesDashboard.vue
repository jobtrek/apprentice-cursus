<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DomainCards from '@/components/DomainCards.vue';
import GradeAccordionItem from '@/components/gradeList/GradeAccordionItem.vue';
import { PageContainer, PageHeader, SectionHeader } from '@/components/page';
import SearchInput from '@/components/SearchInput.vue';
import { Accordion } from '@/components/ui/accordion';
import type { Grade, GradeMenu } from '@/types/grade';

const pageTitle = 'Carnet de notes';

const finalGrade = { title: 'Note finale CFC', weight: '100%', grade: 5.0 };

const domainInformations = [
    { title: 'TPI', weight: '40%', grade: 5.0 },
    { title: 'Compétences en informatique', weight: '30%', grade: 5.0 },
    { title: 'Compétences de base élargies', weight: '10%', grade: 4.5 },
    { title: 'Culture générale', weight: '30%', grade: 5.5 },
];

const columns = [
    { key: 'module', label: 'Module', class: 'w-[30%]' },
    { key: 'subject', label: 'Matière', class: 'w-[30%]' },
    { key: 'value', label: 'Note', class: 'w-[12%]' },
    { key: 'semester', label: 'Semestre', class: 'w-[12%]' },
    { key: 'date', label: 'Date' },
];

const gradeRows: Grade[] = [
    {
        id: 1,
        title: 'Test 1',
        subject: 'Mathématique',
        value: 6,
        semester: 1,
        date: '12.03.2026',
    },
    {
        id: 2,
        title: 'Test 2',
        subject: 'Mathématique',
        value: 5.5,
        semester: 1,
        date: '02.04.2026',
    },
    {
        id: 3,
        title: 'Test 3',
        subject: 'Mathématique',
        value: 4.5,
        semester: 2,
        date: '14.05.2026',
    },
];

const gradeTables: GradeMenu[] = [
    {
        title: 'Compétences en informatique',
        columns,
        grades: gradeRows,
        subMenu: [
            { title: 'Modules école pro', columns, grades: gradeRows },
            { title: 'Modules CIE', columns, grades: gradeRows },
        ],
    },
    { title: 'Compétences de base élargies', columns, grades: gradeRows },
    { title: 'Culture générale', columns, grades: gradeRows },
    { title: 'TPI', columns, grades: gradeRows },
];

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
        ? gradeTables.map((menu) => filterMenu(menu, query))
        : gradeTables;
});

/**
 * Colonnes `lg` dérivées du nombre de domaines (classes littérales requises
 * pour la détection Tailwind — pas d'interpolation dynamique).
 */
const gridCols = computed(() => {
    const cols: Record<number, string> = {
        1: 'lg:grid-cols-1',
        2: 'lg:grid-cols-2',
        3: 'lg:grid-cols-3',
    };
    return cols[domainInformations.length] ?? 'lg:grid-cols-4';
});
</script>

<template>
    <Head :title="pageTitle" />

    <PageContainer size="lg">
        <PageHeader :title="pageTitle" />

        <section class="grid gap-4 sm:grid-cols-2" :class="gridCols">
            <DomainCards
                class="sm:col-span-2 lg:col-span-full"
                :title="finalGrade.title"
                :grade="finalGrade.grade"
                :weight="finalGrade.weight"
            />
            <DomainCards
                v-for="domain in domainInformations"
                :key="domain.title"
                :title="domain.title"
                :grade="domain.grade"
                :weight="domain.weight"
            />
        </section>

        <section class="flex flex-col gap-4 lg:grid">
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
                />
            </Accordion>
        </section>
    </PageContainer>
</template>
