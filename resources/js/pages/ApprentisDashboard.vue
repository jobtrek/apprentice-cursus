<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import ApprenticeDetailSheet from '@/components/apprentice/ApprenticeDetailSheet.vue';
import ApprenticeSearchBar from '@/components/apprentice/ApprenticeSearchBar.vue';
import TabFilter from '@/components/TabFilter.vue';
import ApprenticeYearFilter from '@/components/apprentice/ApprenticeYearFilter.vue';
import { useApprentices, type Apprentice } from '@/composables/useApprentices';
import DataTable from '@/components/DataTable.vue';
import ApprenticeRow from '@/components/apprentice/ApprenticeRow.vue';
import { PageContainer, PageHeader } from '@/components/page';

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

const trackOptions = [
    { label: 'All', value: 'All' },
    { label: 'IT', value: 'IT' },
    { label: 'EC', value: 'EC' },
] as const;
</script>

<template>
    <Head title="Apprentis" />

    <PageContainer>
        <PageHeader title="Apprentis">
            <template #description>
                {{ filtered.length }} apprenti·e{{
                    filtered.length > 1 ? 's' : ''
                }}
                au total
            </template>
        </PageHeader>

        <div class="flex flex-col gap-4">
            <ApprenticeSearchBar v-model="search" />
            <TabFilter v-model="trackFilter" :options="trackOptions" />
            <ApprenticeYearFilter v-model="yearFilter" />
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
            :apprentice="selected"
            v-model:open="sheetOpen"
        />
    </PageContainer>
</template>
