<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import ApprenticeDetailSheet from '@/components/apprentice/ApprenticeDetailSheet.vue';
import ApprenticeRow from '@/components/apprentice/ApprenticeRow.vue';
import DataTable from '@/components/DataTable.vue';
import { PageContainer, PageHeader } from '@/components/page';
import SearchInput from '@/components/SearchInput.vue';
import TabFilter from '@/components/TabFilter.vue';
import { useApprentices } from '@/composables/useApprentices';
import {
    APPRENTICESHIP_FILTER_OPTIONS,
    STATUS_FILTER_OPTIONS,
} from '@/constants/constants';
import type { Apprentice } from '@/types/apprentice';

const props = defineProps<{
    apprentices: Apprentice[];
}>();

const { filtered, search, apprenticeshipFilter, statusFilter } = useApprentices(
    () => props.apprentices,
);

const selected = ref<Apprentice | null>(null);
const sheetOpen = ref(false);

const openDetail = (apprentice: Apprentice) => {
    selected.value = apprentice;
    sheetOpen.value = true;
};

const apprenticeColumns = [
    { key: 'apprentice', label: 'Apprenti·e' },
    { key: 'apprenticeship', label: 'Filière' },
    { key: 'coach', label: 'Coach' },
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
                affiché{{ filtered.length > 1 ? 's' : '' }}
            </template>
        </PageHeader>

        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <SearchInput
                v-model="search"
                placeholder="Rechercher un·e apprenti·e"
                class="lg:max-w-xs"
            />
            <div class="flex flex-wrap gap-3">
                <TabFilter
                    v-model="apprenticeshipFilter"
                    :options="APPRENTICESHIP_FILTER_OPTIONS"
                    label="Filtrer par filière"
                />
                <TabFilter
                    v-model="statusFilter"
                    :options="STATUS_FILTER_OPTIONS"
                    label="Filtrer par statut"
                />
            </div>
        </div>

        <DataTable
            :columns="apprenticeColumns"
            :data="filtered"
            empty-message="Aucun apprenti trouvé."
        >
            <template #row="{ item }">
                <ApprenticeRow :apprentice="item" @select="openDetail" />
            </template>
        </DataTable>

        <ApprenticeDetailSheet
            v-model:open="sheetOpen"
            :apprentice="selected"
        />
    </PageContainer>
</template>
