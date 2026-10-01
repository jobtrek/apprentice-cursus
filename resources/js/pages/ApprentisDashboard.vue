<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ApprenticeDetailSheet from '@/components/apprentice/ApprenticeDetailSheet.vue';
import ApprenticeRow from '@/components/apprentice/ApprenticeRow.vue';
import AssignmentRequestDialog from '@/components/apprentice/AssignmentRequestDialog.vue';
import DataTable from '@/components/DataTable.vue';
import { PageContainer, PageHeader } from '@/components/page';
import SearchInput from '@/components/SearchInput.vue';
import TabFilter from '@/components/TabFilter.vue';
import { TRACK_FILTER_OPTIONS } from '@/constants/constants';
import type { ApprenticeListItem, SupervisorOption } from '@/types/apprentice';

const props = defineProps<{
    /**
     * Apprentis actifs listés (`User::listedApprentices()`) : coach → tous,
     * formateur → sa filière, admin → tous. `canView` dit lesquels s'ouvrent.
     */
    apprentices: ApprenticeListItem[];
    coaches: SupervisorOption[];
    /** Validateurs d'une demande d'attribution (formateurs et admin pour l'instant). */
    validators: SupervisorOption[];
    can: {
        /** Admin local : choix du coach de chaque apprenti·e. */
        manageSupervision: boolean;
    };
}>();

const search = ref('');
const trackFilter = ref<(typeof TRACK_FILTER_OPTIONS)[number]['value']>('All');

const filtered = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.apprentices.filter(
        (apprentice) =>
            apprentice.name.toLowerCase().includes(query) &&
            (trackFilter.value === 'All' ||
                apprentice.track === trackFilter.value),
    );
});

/** Le filtre de filière n'a de sens que si plusieurs filières sont visibles. */
const showTrackFilter = computed(
    () => new Set(props.apprentices.map(({ track }) => track)).size > 1,
);

const selected = ref<ApprenticeListItem | null>(null);
const sheetOpen = ref(false);

const openPreview = (apprentice: ApprenticeListItem) => {
    selected.value = apprentice;
    sheetOpen.value = true;
};

const assignTarget = ref<ApprenticeListItem | null>(null);
const assignOpen = ref(false);

const openAssign = (apprentice: ApprenticeListItem) => {
    assignTarget.value = apprentice;
    assignOpen.value = true;
};

const apprenticeColumns = [
    { key: 'apprentice', label: 'Apprenti·e' },
    { key: 'track', label: 'Filière' },
    { key: 'gradesCount', label: 'Notes', class: 'text-right' },
    { key: 'average', label: 'Moyenne', class: 'text-right' },
    { key: 'lastGrade', label: 'Dernière note' },
    { key: 'coach', label: 'Coach' },
    { key: 'trainer', label: 'Formateur', class: 'hidden lg:table-cell' },
    { key: 'actions', label: 'Accès rapide', class: 'w-px text-right' },
];
</script>

<template>
    <Head title="Apprentis" />

    <PageContainer size="lg">
        <PageHeader title="Apprentis">
            <template #description>
                {{ filtered.length }} apprenti·e{{
                    filtered.length > 1 ? 's' : ''
                }}
                au total
            </template>
        </PageHeader>

        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <SearchInput
                v-model="search"
                placeholder="Rechercher un·e apprenti·e"
                class="lg:max-w-xs"
            />
            <TabFilter
                v-if="showTrackFilter"
                v-model="trackFilter"
                :options="TRACK_FILTER_OPTIONS"
                label="Filtrer par filière"
            />
        </div>

        <DataTable
            :columns="apprenticeColumns"
            :data="filtered"
            empty-message="Aucun apprenti trouvé."
        >
            <template #row="{ item }">
                <ApprenticeRow
                    :apprentice="item"
                    :coaches="can.manageSupervision ? coaches : null"
                    @preview="openPreview"
                    @assign="openAssign"
                />
            </template>
        </DataTable>

        <ApprenticeDetailSheet
            v-model:open="sheetOpen"
            :apprentice="selected"
        />
        <AssignmentRequestDialog
            v-model:open="assignOpen"
            :apprentice="assignTarget"
            :validators="validators"
        />
    </PageContainer>
</template>
