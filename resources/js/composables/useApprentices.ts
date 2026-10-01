import { computed, ref, toValue, type MaybeRefOrGetter } from 'vue';
import type { Apprentice } from '@/types/apprentice';

export const useApprentices = (apprentices: MaybeRefOrGetter<Apprentice[]>) => {
    const search = ref('');
    const trackFilter = ref<'All' | 'IT' | 'EC'>('All');
    const yearFilter = ref<'All' | NonNullable<Apprentice['year']>>('All');

    const filtered = computed(() =>
        toValue(apprentices).filter((a) => {
            const matchesSearch = a.name
                .toLowerCase()
                .includes(search.value.toLowerCase());
            const matchesTrack =
                trackFilter.value === 'All' || a.track === trackFilter.value;
            const matchesYear =
                yearFilter.value === 'All' || a.year === yearFilter.value;
            return matchesSearch && matchesTrack && matchesYear;
        }),
    );

    return { filtered, search, trackFilter, yearFilter };
};
