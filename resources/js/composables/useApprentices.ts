import { computed, ref, toValue, type MaybeRefOrGetter } from 'vue';
import type { Apprentice } from '@/types/apprentice';

/** Recherche et filtres (filière, statut) sur la liste d'apprentis fournie par le serveur. */
export const useApprentices = (apprentices: MaybeRefOrGetter<Apprentice[]>) => {
    const search = ref('');
    const apprenticeshipFilter = ref<'All' | 'IT' | 'EC'>('All');
    const statusFilter = ref<'active' | 'inactive' | 'all'>('active');

    const filtered = computed(() => {
        const needle = search.value.toLowerCase();

        return toValue(apprentices).filter((a) => {
            const matchesSearch = a.name.toLowerCase().includes(needle);
            const matchesApprenticeship =
                apprenticeshipFilter.value === 'All' ||
                a.apprenticeship === apprenticeshipFilter.value;
            const matchesStatus =
                statusFilter.value === 'all' ||
                (statusFilter.value === 'active') === a.isActive;

            return matchesSearch && matchesApprenticeship && matchesStatus;
        });
    });

    return { filtered, search, apprenticeshipFilter, statusFilter };
};
