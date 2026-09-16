<script setup lang="ts">
import { ref } from "vue";
import ApprenticeDetailSheet from "@/components/apprentice/ApprenticeDetailSheet.vue";
import ApprenticeSearchBar from "@/components/apprentice/ApprenticeSearchBar.vue";
import ApprenticeTable from "@/components/apprentice/ApprenticeTable.vue";
import ApprenticeTrackFilter from "@/components/apprentice/ApprenticeTrackFilter.vue";
import ApprenticeYearFilter from "@/components/apprentice/ApprenticeYearFilter.vue";
import { useApprentices, type Apprentice } from "@/composables/useApprentices";

const { filtered, search, trackFilter, yearFilter } = useApprentices();

const selected = ref<Apprentice | null>(null);
const sheetOpen = ref(false);

const openDetail = (apprentice: Apprentice) => {
    selected.value = apprentice;
    sheetOpen.value = true;
};
</script>

<template>
    <div class="w-full max-w-4xl font-sans">
        <h1 class="text-2xl font-semibold mb-1">Apprentis</h1>
        <p class="text-muted-foreground mb-6">
            {{ filtered.length }} apprenti·e{{ filtered.length > 1 ? "s" : "" }}
            au total
        </p>

        <ApprenticeSearchBar v-model="search" />
        <ApprenticeTrackFilter v-model="trackFilter" />
        <ApprenticeYearFilter v-model="yearFilter" />

        <ApprenticeTable :apprentices="filtered" @select="openDetail" />

        <ApprenticeDetailSheet
            :apprentice="selected"
            v-model:open="sheetOpen"
        />
    </div>
</template>
