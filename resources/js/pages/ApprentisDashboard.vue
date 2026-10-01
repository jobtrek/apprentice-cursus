<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowUpDownIcon, UsersIcon, XIcon } from '@lucide/vue';
import { computed, ref, toRef } from 'vue';
import ApprenticeDetailSheet from '@/components/apprentice/ApprenticeDetailSheet.vue';
import ApprenticeRow from '@/components/apprentice/ApprenticeRow.vue';
import DataTable from '@/components/DataTable.vue';
import { PageContainer, PageHeader } from '@/components/page';
import SearchInput from '@/components/SearchInput.vue';
import TabFilter from '@/components/TabFilter.vue';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    SORT_OPTIONS,
    useApprenticeFilters,
    type ScopeFilter,
} from '@/composables/useApprenticeFilters';
import { TRACK_FILTER_OPTIONS } from '@/constants/constants';
import type { ApprenticeListItem, SupervisorOption } from '@/types/apprentice';

const props = defineProps<{
    /**
     * Apprentis actifs listés (`User::listedApprentices()`) : coach → tous,
     * formateur → sa filière, admin → tous. `canView` dit lesquels s'ouvrent.
     */
    apprentices: ApprenticeListItem[];
    coaches: SupervisorOption[];
    can: {
        /** Admin local : choix du coach de chaque apprenti·e. */
        manageSupervision: boolean;
    };
}>();

const { filters, results, hasScope, hasTrackFilter, isFiltered, reset } =
    useApprenticeFilters(toRef(props, 'apprentices'));

const plural = (count: number, word: string): string =>
    `${count} ${word}${count > 1 ? 's' : ''}`;

const followedCount = computed(
    () => props.apprentices.filter(({ canView }) => canView).length,
);
const unassignedCount = computed(
    () => props.apprentices.filter(({ coach }) => coach === null).length,
);

const scopeOptions = computed<{ label: string; value: ScopeFilter }[]>(() => [
    { label: `Mes apprentis (${followedCount.value})`, value: 'mine' },
    { label: `Sans coach (${unassignedCount.value})`, value: 'unassigned' },
    { label: `Tous (${props.apprentices.length})`, value: 'all' },
]);

const sortLabel = computed(
    () => SORT_OPTIONS.find(({ value }) => value === filters.sort)?.label,
);

const selected = ref<ApprenticeListItem | null>(null);
const sheetOpen = ref(false);

const openPreview = (apprentice: ApprenticeListItem) => {
    selected.value = apprentice;
    sheetOpen.value = true;
};

const columns = [
    { key: 'apprentice', label: 'Apprenti·e' },
    { key: 'track', label: 'Filière' },
    { key: 'gradesCount', label: 'Notes', class: 'text-right' },
    { key: 'average', label: 'Moyenne', class: 'text-right' },
    { key: 'lastGrade', label: 'Dernière note' },
    { key: 'coach', label: 'Coach' },
    { key: 'trainer', label: 'Formateur', class: 'hidden xl:table-cell' },
    { key: 'actions', label: 'Actions', class: 'w-px text-right' },
];
</script>

<template>
    <Head title="Apprentis" />

    <PageContainer size="lg">
        <PageHeader title="Apprentis">
            <template #description>
                {{
                    apprentices.length > 1
                        ? `${apprentices.length} apprentis actifs`
                        : `${apprentices.length} apprenti·e actif·ve`
                }}
                <template v-if="unassignedCount > 0">
                    · {{ unassignedCount }} sans coach
                </template>
            </template>
        </PageHeader>

        <Empty v-if="apprentices.length === 0" class="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <UsersIcon />
                </EmptyMedia>
                <EmptyTitle>Aucun apprenti pour le moment</EmptyTitle>
                <EmptyDescription>
                    Les apprentis actifs que vous pouvez suivre apparaîtront ici
                    dès leur synchronisation depuis Entra.
                </EmptyDescription>
            </EmptyHeader>
        </Empty>

        <template v-else>
            <div
                class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center"
            >
                <SearchInput
                    v-model="filters.search"
                    placeholder="Rechercher un apprenti ou un coach"
                    class="lg:max-w-xs"
                />
                <TabFilter
                    v-if="hasScope"
                    v-model="filters.scope"
                    :options="scopeOptions"
                    label="Filtrer par suivi"
                />
                <TabFilter
                    v-if="hasTrackFilter"
                    v-model="filters.track"
                    :options="TRACK_FILTER_OPTIONS"
                    label="Filtrer par filière"
                />

                <Select v-model="filters.sort">
                    <SelectTrigger
                        class="w-full lg:ml-auto lg:w-56"
                        aria-label="Trier la liste"
                    >
                        <ArrowUpDownIcon
                            class="text-muted-foreground"
                            aria-hidden="true"
                        />
                        <SelectValue>{{ sortLabel }}</SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in SORT_OPTIONS"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <p
                v-if="isFiltered"
                class="text-muted-foreground -mt-2 flex items-center gap-2 text-sm"
                aria-live="polite"
            >
                {{ plural(results.length, 'résultat') }}
                <Button
                    variant="link"
                    size="sm"
                    class="h-auto px-0"
                    @click="reset"
                >
                    <XIcon aria-hidden="true" />
                    Effacer les filtres
                </Button>
            </p>

            <DataTable :columns="columns" :data="results">
                <template #row="{ item }">
                    <ApprenticeRow
                        :apprentice="item"
                        :coaches="can.manageSupervision ? coaches : null"
                        @preview="openPreview"
                    />
                </template>

                <template #empty>
                    <div class="flex flex-col items-center gap-2 py-4">
                        <p>Aucun apprenti ne correspond à ces critères.</p>
                        <Button variant="outline" size="sm" @click="reset">
                            Effacer les filtres
                        </Button>
                    </div>
                </template>
            </DataTable>
        </template>

        <ApprenticeDetailSheet
            v-model:open="sheetOpen"
            :apprentice="selected"
        />
    </PageContainer>
</template>
