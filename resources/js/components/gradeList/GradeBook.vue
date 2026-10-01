<script setup lang="ts">
import { useRemember } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import DataTable from '@/components/DataTable.vue';
import DomainCards from '@/components/DomainCards.vue';
import FilterSelect from '@/components/FilterSelect.vue';
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
import { PASSING_GRADE } from '@/data/dashboard';
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
type ResultFilter = 'all' | 'insufficient' | 'commented';

type GradeBookView = {
    tab: string;
    sub: string;
    semester: string;
    result: ResultFilter;
};

// Avec un objet reactive, useRemember renvoie ce même objet (pas un Ref).
const view = useRemember(
    reactive<GradeBookView>({
        tab: ALL,
        sub: ALL,
        semester: ALL,
        result: 'all',
    }),
    'GradeBook',
) as GradeBookView;
// Un état mémorisé avant l'ajout de ces filtres n'a pas ces champs.
view.semester ??= ALL;
view.result ??= 'all';

/** Semestres ayant au moins une note. */
const semesterOptions = computed(() => [
    { value: ALL, label: 'Tous les semestres' },
    ...[...new Set(props.grades.map((grade) => grade.semester))]
        .sort((a, b) => a - b)
        .map((semester) => ({
            value: String(semester),
            label: `Semestre ${semester}`,
        })),
]);

const RESULT_OPTIONS: { value: ResultFilter; label: string }[] = [
    { value: 'all', label: 'Tous les résultats' },
    { value: 'insufficient', label: `Insuffisantes (< ${PASSING_GRADE})` },
    { value: 'commented', label: 'Commentées' },
];

function matchesFilters(grade: Grade): boolean {
    if (view.semester !== ALL && String(grade.semester) !== view.semester) {
        return false;
    }

    switch (view.result) {
        case 'insufficient':
            return grade.value < PASSING_GRADE;
        case 'commented':
            return (grade.comments_count ?? 0) > 0;
        default:
            return true;
    }
}

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

    return source.filter(
        (grade) =>
            matchesFilters(grade) && (query === '' || matches(grade, query)),
    );
});

const emptyMessage = computed(() =>
    search.value.trim() || view.semester !== ALL || view.result !== 'all'
        ? 'Aucune note ne correspond à ces critères.'
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

            <FilterSelect
                v-model="view.semester"
                :options="semesterOptions"
                :neutral="ALL"
                label="Filtrer par semestre"
            />
            <FilterSelect
                v-model="view.result"
                :options="RESULT_OPTIONS"
                neutral="all"
                label="Filtrer par résultat"
            />

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
