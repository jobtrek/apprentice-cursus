import { useRemember } from '@inertiajs/vue3';
import { computed, reactive, type Ref } from 'vue';
import { sortableDate } from '@/lib/apprentice';
import type { ApprenticeListItem } from '@/types/apprentice';

export type TrackFilter = 'All' | 'IT' | 'EC';

export type ApprenticeSort = 'name' | 'average' | 'recent';

export const SORT_OPTIONS: { value: ApprenticeSort; label: string }[] = [
    { value: 'name', label: 'Nom (A → Z)' },
    { value: 'average', label: 'Moyenne la plus basse' },
    { value: 'recent', label: 'Note la plus récente' },
];

type Filters = {
    search: string;
    track: TrackFilter;
    sort: ApprenticeSort;
};

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

/**
 * Recherche, filtres et tri de la liste des apprentis. L'état est mémorisé
 * par Inertia : il est retrouvé en revenant sur la liste depuis un profil.
 */
export const useApprenticeFilters = (
    apprentices: Ref<ApprenticeListItem[]>,
) => {
    const defaults = (): Filters => ({
        search: '',
        track: 'All',
        sort: 'name',
    });

    // Avec un objet reactive, useRemember renvoie ce même objet (pas un Ref).
    const filters = useRemember(
        reactive(defaults()),
        'ApprentisDashboard',
    ) as Filters;

    const results = computed(() => {
        const query = filters.search.trim().toLocaleLowerCase('fr');

        return apprentices.value
            .filter(
                (apprentice) =>
                    (query === '' ||
                        apprentice.name
                            .toLocaleLowerCase('fr')
                            .includes(query) ||
                        apprentice.coach
                            ?.toLocaleLowerCase('fr')
                            .includes(query)) &&
                    (filters.track === 'All' ||
                        apprentice.track === filters.track),
            )
            .sort(compare[filters.sort]);
    });

    /** Le filtre de filière n'a de sens que si plusieurs filières sont visibles. */
    const hasTrackFilter = computed(
        () => new Set(apprentices.value.map(({ track }) => track)).size > 1,
    );

    const isFiltered = computed(
        () => filters.search.trim() !== '' || filters.track !== 'All',
    );

    const reset = (): void => {
        Object.assign(filters, { ...defaults(), sort: filters.sort });
    };

    return {
        filters,
        results,
        hasTrackFilter,
        isFiltered,
        reset,
    };
};
