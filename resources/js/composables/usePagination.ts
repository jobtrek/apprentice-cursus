import { computed, ref, type Ref } from 'vue';

// ponytail: pagination côté client, la liste complète est déjà chargée ;
// paginer côté serveur si elle devient trop lourde.
export const usePagination = <T>(items: Ref<T[]>, pageSize = 10) => {
    const page = ref(1);
    const pageCount = computed(() =>
        Math.max(1, Math.ceil(items.value.length / pageSize)),
    );
    /** Bornée : la liste peut rétrécir sous la page courante (ex. rechargement, filtre). */
    const currentPage = computed({
        get: () => Math.min(page.value, pageCount.value),
        set: (value: number) => {
            page.value = value;
        },
    });
    const pageItems = computed(() =>
        items.value.slice(
            (currentPage.value - 1) * pageSize,
            currentPage.value * pageSize,
        ),
    );

    return { currentPage, pageCount, pageItems };
};
