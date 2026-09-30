import { computed, ref } from 'vue';
import rawApprentices from '@/data/apprentices.json';

export interface Apprentice {
    id: string;
    name: string;
    avatarUrl?: string;
    track: 'IT' | 'EC';
    year: '1ère' | '2ème' | '3ème' | '4ème';
    coach?: string;
    trainer?: string;
}

const apprentices = ref<Apprentice[]>(rawApprentices as Apprentice[]);

/** Apprenti·e correspondant à l'identifiant d'URL, s'il existe. */
export const findApprentice = (id: number | string): Apprentice | undefined =>
    apprentices.value.find((apprentice) => apprentice.id === String(id));

export const useApprentices = () => {
    const search = ref('');
    const trackFilter = ref<'All' | 'IT' | 'EC'>('All');
    const yearFilter = ref<'All' | Apprentice['year']>('All');

    const filtered = computed(() =>
        apprentices.value.filter((a) => {
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

    return { apprentices, filtered, search, trackFilter, yearFilter };
};
