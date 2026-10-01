<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowUpDownIcon, UserPlusIcon, UsersIcon, XIcon } from '@lucide/vue';
import { computed, ref, toRef } from 'vue';
import AddActionButton from '@/components/AddActionButton.vue';
import ApprenticeDetailSheet from '@/components/apprentice/ApprenticeDetailSheet.vue';
import ApprenticeRow from '@/components/apprentice/ApprenticeRow.vue';
import AssignApprenticeDialog from '@/components/apprentice/AssignApprenticeDialog.vue';
import DataTable from '@/components/DataTable.vue';
import { PageContainer, PageHeader } from '@/components/page';
import SearchInput from '@/components/SearchInput.vue';
import TabFilter from '@/components/TabFilter.vue';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyContent,
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
} from '@/composables/useApprenticeFilters';
import { TRACK_FILTER_OPTIONS } from '@/constants/constants';
import type {
    ApprenticeListItem,
    AssignableApprentice,
    AssignSelfAs,
    SupervisorOption,
    TrainerOption,
} from '@/types/apprentice';

const props = defineProps<{
    /**
     * Apprentis actifs suivis (`User::listedApprentices()`) : coach → ses
     * coachés, formateur → ses apprentis de sa filière, admin → tous.
     */
    apprentices: ApprenticeListItem[];
    /** Proposés par « Ajouter un apprenti ». */
    assignable: AssignableApprentice[];
    coaches: SupervisorOption[];
    trainers: TrainerOption[];
    can: {
        /** Coach ou formateur : rôle pris en ajoutant un apprenti. */
        assignSelfAs: AssignSelfAs | null;
        /** Admin local : choix du coach et du formateur de chaque apprenti·e. */
        manageSupervision: boolean;
    };
}>();

const { filters, results, hasTrackFilter, isFiltered, reset } =
    useApprenticeFilters(toRef(props, 'apprentices'));

const plural = (count: number, word: string): string =>
    `${count} ${word}${count > 1 ? 's' : ''}`;

const summary = computed(() => {
    const count = props.apprentices.length;
    const followed = `${plural(count, 'apprenti')} ${props.can.assignSelfAs ? 'suivi' : 'actif'}${count > 1 ? 's' : ''}`;
    const available = props.assignable.length;

    return props.can.assignSelfAs && available > 0
        ? `${followed} · ${plural(available, 'disponible')} à ajouter`
        : followed;
});

const sortLabel = computed(
    () => SORT_OPTIONS.find(({ value }) => value === filters.sort)?.label,
);

const assignOpen = ref(false);

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
    { key: 'trainer', label: 'Formateur', class: 'hidden lg:table-cell' },
    { key: 'actions', label: 'Actions', class: 'w-px text-right' },
];
</script>

<template>
    <Head title="Apprentis" />

    <PageContainer size="lg">
        <PageHeader
            :title="can.assignSelfAs ? 'Mes apprentis' : 'Apprentis'"
            :description="summary"
        >
            <template v-if="can.assignSelfAs" #actions>
                <AddActionButton
                    label="Ajouter un apprenti"
                    data-test="assign-apprentice-button"
                    @click="assignOpen = true"
                />
            </template>
        </PageHeader>

        <Empty v-if="apprentices.length === 0" class="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <UsersIcon />
                </EmptyMedia>
                <EmptyTitle>
                    {{
                        can.assignSelfAs
                            ? 'Vous ne suivez encore aucun apprenti'
                            : 'Aucun apprenti pour le moment'
                    }}
                </EmptyTitle>
                <EmptyDescription>
                    {{
                        can.assignSelfAs
                            ? 'Ajoutez les apprentis que vous suivez pour voir leurs notes et leur portfolio.'
                            : 'Les apprentis actifs apparaîtront ici dès leur synchronisation depuis Entra.'
                    }}
                </EmptyDescription>
            </EmptyHeader>
            <EmptyContent v-if="can.assignSelfAs">
                <Button @click="assignOpen = true">
                    <UserPlusIcon aria-hidden="true" />
                    Ajouter un apprenti
                </Button>
            </EmptyContent>
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
                        :trainers="can.manageSupervision ? trainers : null"
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

        <AssignApprenticeDialog
            v-if="can.assignSelfAs"
            v-model:open="assignOpen"
            :as="can.assignSelfAs"
            :assignable="assignable"
        />
    </PageContainer>
</template>
