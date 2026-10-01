import { useRemember } from '@inertiajs/vue3';
import { computed, reactive, type Ref } from 'vue';
import { sortableDate } from '@/lib/apprentice';
import type { ApprenticeListItem } from '@/types/apprentice';

export type TrackFilter = 'All' | 'IT' | 'EC';

/** Coach : ses apprentis, ceux sans coach, ou tous. */
export type ScopeFilter = 'mine' | 'unassigned' | 'all';

export type ApprenticeSort = 'name' | 'average' | 'recent';

export const SORT_OPTIONS: { value: ApprenticeSort; label: string }[] = [
    { value: 'name', label: 'Nom (A → Z)' },
    { value: 'average', label: 'Moyenne la plus basse' },
    { value: 'recent', label: 'Note la plus récente' },
];

type Filters = {
    search: string;
    track: TrackFilter;
    scope: ScopeFilter;
    sort: ApprenticeSort;
};

/** Sans valeur, une moyenne ou une date passe après toutes les autres. */
const compare: Record<
    ApprenticeSort,
    (a: ApprenticeListItem, b: ApprenticeListItem) => number
> = {
    name: (a, b) => a.name.localeCompare(b.name, 'fr'),
    average: (a, b) =>
        (a.stats?.average ?? Infinity) - (b.stats?.average ?? Infinity),
    recent: (a, b) =>
        sortableDate(b.stats?.last_grade_date ?? '').localeCompare(
            sortableDate(a.stats?.last_grade_date ?? ''),
        ),
};

const matchesScope = (
    apprentice: ApprenticeListItem,
    scope: ScopeFilter,
): boolean => {
    switch (scope) {
        case 'mine':
            return apprentice.canView;
        case 'unassigned':
            return apprentice.coach === null;
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
    /** Un coach voit tous les apprentis mais n'en ouvre qu'une partie. */
    const hasScope = computed(() =>
        apprentices.value.some((apprentice) => !apprentice.canView),
    );

    const defaults = (): Filters => ({
        search: '',
        track: 'All',
        // Un coach arrive sur les siens, s'il en suit déjà.
        scope:
            hasScope.value && apprentices.value.some(({ canView }) => canView)
                ? 'mine'
                : 'all',
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
                        apprentice.track === filters.track) &&
                    matchesScope(apprentice, filters.scope),
            )
            .sort(compare[filters.sort]);
    });

    /** Le filtre de filière n'a de sens que si plusieurs filières sont visibles. */
    const hasTrackFilter = computed(
        () => new Set(apprentices.value.map(({ track }) => track)).size > 1,
    );

    const isFiltered = computed(() => {
        const initial = defaults();

        return (
            filters.search.trim() !== '' ||
            filters.track !== initial.track ||
            filters.scope !== initial.scope
        );
    });

    const reset = (): void => {
        Object.assign(filters, { ...defaults(), sort: filters.sort });
    };

    return {
        filters,
        results,
        hasScope,
        hasTrackFilter,
        isFiltered,
        reset,
    };
};
