<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import ApprenticeDetailSheet from '@/components/apprentice/ApprenticeDetailSheet.vue';
import ApprenticeRow from '@/components/apprentice/ApprenticeRow.vue';
import DataTable from '@/components/DataTable.vue';
import { PageContainer, PageHeader } from '@/components/page';
import SearchInput from '@/components/SearchInput.vue';
import TabFilter from '@/components/TabFilter.vue';
import { useApprentices, type Apprentice } from '@/composables/useApprentices';
import {
    TRACK_FILTER_OPTIONS,
    YEAR_FILTER_OPTIONS,
} from '@/constants/constants';

const { filtered, search, trackFilter, yearFilter } = useApprentices();

const selected = ref<Apprentice | null>(null);
const sheetOpen = ref(false);

const openDetail = (apprentice: Apprentice) => {
    selected.value = apprentice;
    sheetOpen.value = true;
};

const apprenticeColumns = [
    { key: 'apprentice', label: 'Apprenti·e' },
    { key: 'track', label: 'Filière' },
    { key: 'year', label: 'Année' },
    { key: 'coach', label: 'Coach' },
    { key: 'trainer', label: 'Formateur' },
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
            <div class="flex flex-wrap gap-3">
                <TabFilter
                    v-model="trackFilter"
                    :options="TRACK_FILTER_OPTIONS"
                    label="Filtrer par filière"
                />
                <TabFilter
                    v-model="yearFilter"
                    :options="YEAR_FILTER_OPTIONS"
                    label="Filtrer par année"
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
