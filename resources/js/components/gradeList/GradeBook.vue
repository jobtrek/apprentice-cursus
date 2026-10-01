<script setup lang="ts">
import { useRemember } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import DataTable from '@/components/DataTable.vue';
import DomainCards from '@/components/DomainCards.vue';
import GradeListElement from '@/components/gradeList/GradeListElement.vue';
import { SectionHeader } from '@/components/page';
import SearchInput from '@/components/SearchInput.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DOMAIN_GRADES,
    FINAL_GRADE,
    GRADE_COLUMNS,
    gradeTables,
} from '@/data/gradebook';
import type { Grade, GradeMenu } from '@/types/grade';
import type { RouteDefinition } from '@/wayfinder';

const props = defineProps<{
    /** Notes réelles de l'apprenti·e, réparties dans chaque domaine. */
    grades: Grade[];
    /** Page de détail d'une note : diffère entre l'apprenti et le coach. */
    gradeHref: (grade: Grade) => RouteDefinition<'get'>;
}>();

const ALL = 'all';

/**
 * Domaine et sous-filtre choisis, gardés dans l'historique Inertia : en
 * revenant d'une note, on retrouve le carnet tel qu'on l'avait laissé.
 */
type GradeBookView = { tab: string; sub: string };

// Avec un objet reactive, useRemember renvoie ce même objet (pas un Ref).
const view = useRemember(
    reactive<GradeBookView>({ tab: ALL, sub: ALL }),
    'GradeBook',
) as GradeBookView;

const search = ref('');

const tables = computed(() => gradeTables(props.grades));

const activeMenu = computed<GradeMenu | undefined>(() =>
    tables.value.find((menu) => menu.title === view.tab),
);

const activeSubMenu = computed<GradeMenu | undefined>(() =>
    activeMenu.value?.subMenu?.find((sub) => sub.title === view.sub),
);

function selectDomain(tab: unknown): void {
    view.tab = typeof tab === 'string' ? tab : ALL;
    view.sub = ALL;
}

function selectSub(sub: unknown): void {
    view.sub = typeof sub === 'string' ? sub : ALL;
}

function matches(grade: Grade, query: string): boolean {
    return `${grade.title} ${grade.subject}`.toLowerCase().includes(query);
}

const visibleGrades = computed(() => {
    const source =
        activeSubMenu.value?.grades ?? activeMenu.value?.grades ?? props.grades;
    const query = search.value.trim().toLowerCase();

    return query ? source.filter((grade) => matches(grade, query)) : source;
});

const emptyMessage = computed(() =>
    search.value.trim()
        ? 'Aucune note ne correspond à la recherche.'
        : 'Aucune note pour l’instant.',
);

const gridCols = computed(() => {
    const cols: Record<number, string> = {
        1: 'lg:grid-cols-1',
        2: 'lg:grid-cols-2',
        3: 'lg:grid-cols-3',
    };
    return cols[DOMAIN_GRADES.length] ?? 'lg:grid-cols-4';
});
</script>

<template>
    <section class="grid gap-4 sm:grid-cols-2" :class="gridCols">
        <DomainCards
            class="sm:col-span-2 lg:col-span-full"
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
        <SectionHeader title="Notes" />

        <div
            class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center"
        >
            <Select :model-value="view.tab" @update:model-value="selectDomain">
                <SelectTrigger
                    class="w-full sm:w-72"
                    aria-label="Domaine"
                    data-test="grade-domain-select"
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">
                        Tous les domaines ({{ grades.length }})
                    </SelectItem>
                    <SelectItem
                        v-for="menu in tables"
                        :key="menu.title"
                        :value="menu.title"
                    >
                        {{ menu.title }} ({{ menu.grades.length }})
                    </SelectItem>
                </SelectContent>
            </Select>

            <!-- Seuls certains domaines (Informatique) ont des sous-domaines. -->
            <Select
                v-if="activeMenu?.subMenu"
                :model-value="view.sub"
                @update:model-value="selectSub"
            >
                <SelectTrigger class="w-full sm:w-56" aria-label="Modules">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">
                        Tous les modules ({{ activeMenu.grades.length }})
                    </SelectItem>
                    <SelectItem
                        v-for="sub in activeMenu.subMenu"
                        :key="sub.title"
                        :value="sub.title"
                    >
                        {{ sub.title }} ({{ sub.grades.length }})
                    </SelectItem>
                </SelectContent>
            </Select>

            <SearchInput
                v-model="search"
                placeholder="Rechercher une note"
                class="w-full sm:ml-auto sm:w-64"
            />
        </div>

        <DataTable
            :columns="GRADE_COLUMNS"
            :data="visibleGrades"
            :empty-message="emptyMessage"
        >
            <template #row="{ item }">
                <GradeListElement v-bind="item" :href="gradeHref(item)" />
            </template>
        </DataTable>
    </section>
</template>
