import { useRemember } from '@inertiajs/vue3';
import { computed, reactive, type Ref } from 'vue';
import { PASSING_GRADE } from '@/data/dashboard';
import { hasNoRecentGrade, sortableDate } from '@/lib/apprentice';
import type { ApprenticeListItem } from '@/types/apprentice';

export type TrackFilter = 'All' | 'IT' | 'EC';

export type ApprenticeSort = 'name' | 'average' | 'recent';

/** Une option de liste déroulante. */
export type FilterOption<T extends string> = { value: T; label: string };

export const SORT_OPTIONS: FilterOption<ApprenticeSort>[] = [
    { value: 'name', label: 'Nom (A → Z)' },
    { value: 'average', label: 'Moyenne la plus basse' },
    { value: 'recent', label: 'Note la plus récente' },
];

export type YearFilter = 'all' | '1' | '2' | '3' | '4';

export const YEAR_OPTIONS: FilterOption<YearFilter>[] = [
    { value: 'all', label: 'Toutes les années' },
    { value: '1', label: '1re année' },
    { value: '2', label: '2e année' },
    { value: '3', label: '3e année' },
    { value: '4', label: '4e année' },
];

export type VariantFilter = 'all' | 'mp' | 'standard';

export const VARIANT_OPTIONS: FilterOption<VariantFilter>[] = [
    { value: 'all', label: 'Toutes les variantes' },
    { value: 'standard', label: 'Standard' },
    { value: 'mp', label: 'Maturité (MP)' },
];

export type SituationFilter = 'all' | 'insufficient' | 'no-grades' | 'stale';

export const SITUATION_OPTIONS: FilterOption<SituationFilter>[] = [
    { value: 'all', label: 'Toutes les situations' },
    { value: 'insufficient', label: 'Moyenne insuffisante' },
    { value: 'no-grades', label: 'Aucune note' },
    { value: 'stale', label: 'Sans note récente' },
];

/** Admin local : apprentis à qui il manque un superviseur. */
export type SupervisionFilter = 'all' | 'no-coach' | 'no-trainer';

export const SUPERVISION_OPTIONS: FilterOption<SupervisionFilter>[] = [
    { value: 'all', label: 'Tous les suivis' },
    { value: 'no-coach', label: 'Sans coach' },
    { value: 'no-trainer', label: 'Sans formateur' },
];

type Filters = {
    search: string;
    track: TrackFilter;
    year: YearFilter;
    variant: VariantFilter;
    situation: SituationFilter;
    supervision: SupervisionFilter;
    sort: ApprenticeSort;
};

const defaults = (): Filters => ({
    search: '',
    track: 'All',
    year: 'all',
    variant: 'all',
    situation: 'all',
    supervision: 'all',
    sort: 'name',
});

/** Sans valeur, une moyenne ou une date passe après toutes les autres. */
const compare: Record<
    ApprenticeSort,
    (a: ApprenticeListItem, b: ApprenticeListItem) => number
> = {
    name: (a, b) => a.name.localeCompare(b.name, 'fr'),
    average: (a, b) =>
        (a.stats.average ?? Infinity) - (b.stats.average ?? Infinity),
    recent: (a, b) =>
        sortableDate(b.stats.last_grade_date ?? '').localeCompare(
            sortableDate(a.stats.last_grade_date ?? ''),
        ),
};

const matchesSearch = (apprentice: ApprenticeListItem, query: string) =>
    query === '' ||
    [apprentice.name, apprentice.coach, apprentice.trainer].some((value) =>
        value?.toLocaleLowerCase('fr').includes(query),
    );

const matchesSituation = (
    { stats }: ApprenticeListItem,
    situation: SituationFilter,
): boolean => {
    switch (situation) {
        case 'insufficient':
            return stats.average !== null && stats.average < PASSING_GRADE;
        case 'no-grades':
            return stats.grades_count === 0;
        case 'stale':
            return hasNoRecentGrade(stats.last_grade_date);
        default:
            return true;
    }
};

const matchesSupervision = (
    apprentice: ApprenticeListItem,
    supervision: SupervisionFilter,
): boolean => {
    switch (supervision) {
        case 'no-coach':
            return apprentice.coach === null;
        case 'no-trainer':
            return apprentice.trainer === null;
        default:
            return true;
    }
};

/**
 * Recherche, filtres et tri de la liste des apprentis. L'état est mémorisé
 * par Inertia : il est retrouvé en revenant sur la liste depuis un profil.
 */
export const useApprenticeFilters = (
    apprentices: Ref<ApprenticeListItem[]>,
) => {
    // Avec un objet reactive, useRemember renvoie ce même objet (pas un Ref).
    // Complété par les valeurs par défaut : un état mémorisé par une version
    // précédente de la page peut ne pas avoir tous les champs.
    const filters = useRemember(
        reactive(defaults()),
        'ApprentisDashboard',
    ) as Filters;
    Object.assign(filters, { ...defaults(), ...filters });

    const results = computed(() => {
        const query = filters.search.trim().toLocaleLowerCase('fr');

        return apprentices.value
            .filter(
                (apprentice) =>
                    matchesSearch(apprentice, query) &&
                    (filters.track === 'All' ||
                        apprentice.track === filters.track) &&
                    (filters.year === 'all' ||
                        String(apprentice.year) === filters.year) &&
                    (filters.variant === 'all' ||
                        apprentice.isMp === (filters.variant === 'mp')) &&
                    matchesSituation(apprentice, filters.situation) &&
                    matchesSupervision(apprentice, filters.supervision),
            )
            .sort(compare[filters.sort]);
    });

    /** Le filtre de filière n'a de sens que si plusieurs filières sont visibles. */
    const hasTrackFilter = computed(
        () => new Set(apprentices.value.map(({ track }) => track)).size > 1,
    );

    /** Nombre de filtres actifs, hors recherche et tri. */
    const activeCount = computed(() => {
        const initial = defaults();

        return (
            ['track', 'year', 'variant', 'situation', 'supervision'] as const
        ).filter((key) => filters[key] !== initial[key]).length;
    });

    const isFiltered = computed(
        () => filters.search.trim() !== '' || activeCount.value > 0,
    );

    const reset = (): void => {
        Object.assign(filters, { ...defaults(), sort: filters.sort });
    };

    return {
        filters,
        results,
        hasTrackFilter,
        activeCount,
        isFiltered,
        reset,
    };
};
