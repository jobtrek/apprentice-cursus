<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { LibraryIcon, PlusIcon, SearchXIcon, XIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import DataTable from '@/components/DataTable.vue';
import FilterSelect from '@/components/FilterSelect.vue';
import { PageContainer, PageHeader } from '@/components/page';
import SearchInput from '@/components/SearchInput.vue';
import SubjectDialog from '@/components/subject/SubjectDialog.vue';
import SubjectRow from '@/components/subject/SubjectRow.vue';
import TabFilter from '@/components/TabFilter.vue';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { TRACK_FILTER_OPTIONS } from '@/constants/constants';
import subjectRoutes from '@/routes/subjects';
import type { Subject, SubjectFilters, SubjectParent } from '@/types/subject';

const props = defineProps<{
    subjects: Subject[];
    total: number;
    filters: SubjectFilters;
    domains: { value: string; label: string }[];
    parents: SubjectParent[];
}>();

const columns = [
    { key: 'name', label: 'Matière', class: 'w-1/2' },
    { key: 'parent', label: 'Domaine' },
    { key: 'tracks', label: 'Filière' },
    { key: 'grades', label: 'Notes', class: 'text-right' },
    { key: 'actions', label: 'Actions', srOnly: true, class: 'w-0' },
];

const search = ref(props.filters.search);

const dialogOpen = ref(false);
const editing = ref<Subject | undefined>();

const isFiltered = computed(
    () =>
        props.filters.search !== '' ||
        props.filters.track !== 'All' ||
        props.filters.domain !== 'all',
);

function apply(filters: Partial<SubjectFilters>): void {
    const next = { ...props.filters, ...filters };

    router.get(
        subjectRoutes.index.url(),
        {
            search: next.search || undefined,
            track: next.track === 'All' ? undefined : next.track,
            domain: next.domain === 'all' ? undefined : next.domain,
        },
        {
            only: ['subjects', 'filters', 'domains'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

watchDebounced(search, (value) => apply({ search: value }), {
    debounce: 300,
});

function reset(): void {
    search.value = '';
    apply({ search: '', track: 'All', domain: 'all' });
}

/** Refus du serveur (matière utilisée par une note), hors formulaire. */
const page = usePage();
const deleteError = computed(
    () => page.props.errors?.subject as string | undefined,
);

function openCreate(): void {
    editing.value = undefined;
    dialogOpen.value = true;
}

function openEdit(subject: Subject): void {
    editing.value = subject;
    dialogOpen.value = true;
}
</script>

<template>
    <Head title="Matières" />

    <PageContainer size="lg">
        <PageHeader
            title="Matières"
            description="Les matières des arbres de notes, celles sur lesquelles les apprentis saisissent leurs notes."
        >
            <template #actions>
                <Button data-test="new-subject-button" @click="openCreate">
                    <PlusIcon aria-hidden="true" />
                    Nouvelle matière
                </Button>
            </template>
        </PageHeader>

        <section
            class="bg-card flex flex-col overflow-hidden rounded-xl border shadow-xs"
            aria-label="Liste des matières"
        >
            <div
                class="flex flex-wrap items-center gap-2 border-b p-4"
                role="group"
                aria-label="Recherche et filtres"
            >
                <SearchInput
                    v-model="search"
                    placeholder="Rechercher une matière"
                    class="w-full sm:w-72"
                />
                <TabFilter
                    :model-value="filters.track"
                    :options="TRACK_FILTER_OPTIONS"
                    label="Filtrer par filière"
                    @update:model-value="apply({ track: $event })"
                />
                <FilterSelect
                    :model-value="filters.domain"
                    :options="domains"
                    neutral="all"
                    label="Filtrer par domaine"
                    class="sm:w-72"
                    @update:model-value="apply({ domain: $event })"
                />
                <Button
                    v-if="isFiltered"
                    variant="link"
                    size="sm"
                    class="h-auto px-0"
                    @click="reset"
                >
                    <XIcon aria-hidden="true" />
                    Effacer les filtres
                </Button>
            </div>

            <p
                v-if="deleteError"
                class="text-destructive border-b px-4 py-3 text-sm"
                role="alert"
            >
                {{ deleteError }}
            </p>

            <Empty v-if="total === 0" class="rounded-none">
                <EmptyHeader>
                    <EmptyMedia variant="icon"><LibraryIcon /></EmptyMedia>
                    <EmptyTitle>Aucune matière</EmptyTitle>
                    <EmptyDescription>
                        Les arbres de notes ne sont pas encore chargés.
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>

            <Empty v-else-if="subjects.length === 0" class="rounded-none">
                <EmptyHeader>
                    <EmptyMedia variant="icon"><SearchXIcon /></EmptyMedia>
                    <EmptyTitle>Aucun résultat</EmptyTitle>
                    <EmptyDescription>
                        Aucune matière ne correspond à ces critères.
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>

            <DataTable
                v-else
                :columns="columns"
                :data="subjects"
                class="rounded-none border-0"
            >
                <template #row="{ item }">
                    <SubjectRow :subject="item" @edit="openEdit" />
                </template>
            </DataTable>

            <p class="text-muted-foreground border-t px-4 py-3 text-xs">
                Une matière déjà utilisée par une note ne peut pas être
                supprimée, seulement modifiée.
            </p>
        </section>

        <SubjectDialog
            v-model:open="dialogOpen"
            :parents="parents"
            :subject="editing"
        />
    </PageContainer>
</template>
