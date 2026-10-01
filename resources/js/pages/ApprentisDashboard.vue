<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AddActionButton from '@/components/AddActionButton.vue';
import ApprenticeDetailSheet from '@/components/apprentice/ApprenticeDetailSheet.vue';
import ApprenticeRow from '@/components/apprentice/ApprenticeRow.vue';
import AssignApprenticeDialog from '@/components/apprentice/AssignApprenticeDialog.vue';
import DataTable from '@/components/DataTable.vue';
import { PageContainer, PageHeader } from '@/components/page';
import SearchInput from '@/components/SearchInput.vue';
import TabFilter from '@/components/TabFilter.vue';
import { TRACK_FILTER_OPTIONS } from '@/constants/constants';
import type {
    ApprenticeListItem,
    AssignableApprentice,
    AssignSelfAs,
    SupervisorOption,
    TrainerOption,
} from '@/types/apprentice';

const props = defineProps<{
    /** Apprentis supervisés : coach et formateur → les leurs, admin → tous. */
    apprentices: ApprenticeListItem[];
    assignable: AssignableApprentice[];
    coaches: SupervisorOption[];
    trainers: TrainerOption[];
    can: {
        /** Bouton « Ajouter un·e apprenti·e » : en tant que coach ou formateur. */
        assignSelfAs: AssignSelfAs | null;
        /** Admin local : choix du coach et du formateur de chaque apprenti·e. */
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

const assignOpen = ref(false);

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
        <PageHeader :title="can.assignSelfAs ? 'Mes apprentis' : 'Apprentis'">
            <template #description>
                {{ filtered.length }} apprenti·e{{
                    filtered.length > 1 ? 's' : ''
                }}
                au total
            </template>
            <template v-if="can.assignSelfAs" #actions>
                <AddActionButton
                    label="Ajouter un·e apprenti·e"
                    data-test="assign-apprentice-button"
                    @click="assignOpen = true"
                />
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
            :empty-message="
                apprentices.length === 0 && can.assignSelfAs
                    ? 'Vous ne suivez encore aucun apprenti. Utilisez le bouton + pour en ajouter.'
                    : 'Aucun apprenti trouvé.'
            "
        >
            <template #row="{ item }">
                <ApprenticeRow
                    :apprentice="item"
                    :coaches="can.manageSupervision ? coaches : null"
                    :trainers="can.manageSupervision ? trainers : null"
                    @preview="openPreview"
                />
            </template>
        </DataTable>

        <ApprenticeDetailSheet
            v-model:open="sheetOpen"
            :apprentice="selected"
        />

        <AssignApprenticeDialog
            v-if="can.assignSelfAs"
            v-model:open="assignOpen"
            :as="can.assignSelfAs"
            :assignable="assignable"
        />
    </PageContainer>
</template>
