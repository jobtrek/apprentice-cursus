<script setup lang="ts">
import { Head, useRemember } from '@inertiajs/vue3';
import {
    ArrowUpDownIcon,
    LayoutGridIcon,
    ListIcon,
    SearchXIcon,
    UserPlusIcon,
    UsersIcon,
    XIcon,
} from '@lucide/vue';
import { computed, ref, toRef } from 'vue';
import ApprenticeCard from '@/components/apprentice/ApprenticeCard.vue';
import ApprenticeDetailSheet from '@/components/apprentice/ApprenticeDetailSheet.vue';
import ApprenticeRow from '@/components/apprentice/ApprenticeRow.vue';
import AssignApprenticeDialog from '@/components/apprentice/AssignApprenticeDialog.vue';
import SituationTiles from '@/components/apprentice/SituationTiles.vue';
import DataTable from '@/components/DataTable.vue';
import FilterSelect from '@/components/FilterSelect.vue';
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
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import {
    SITUATION_OPTIONS,
    SORT_OPTIONS,
    SUPERVISION_OPTIONS,
    useApprenticeFilters,
    VARIANT_OPTIONS,
    YEAR_OPTIONS,
} from '@/composables/useApprenticeFilters';
import { TRACK_FILTER_OPTIONS } from '@/constants/constants';
import { PASSING_GRADE } from '@/data/dashboard';
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

const { filters, results, hasTrackFilter, activeCount, isFiltered, reset } =
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

/** Grands écrans : tableau ou cartes. Les petits écrans ont toujours les cartes. */
type ListLayout = 'table' | 'grid';
const layout = useRemember(
    ref<ListLayout>('table'),
    'ApprentisDashboard:layout',
);

const selectLayout = (value: unknown): void => {
    // Un ToggleGroup « single » renvoie une valeur vide si on reclique l'actif.
    if (value === 'table' || value === 'grid') {
        layout.value = value;
    }
};

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
    { key: 'situation', label: 'Situation' },
    { key: 'average', label: 'Moyenne' },
    { key: 'activity', label: 'Activité' },
    { key: 'coach', label: 'Coach' },
    { key: 'trainer', label: 'Formateur' },
    { key: 'actions', label: 'Actions', class: 'w-px', srOnly: true },
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
                <Button
                    data-test="assign-apprentice-button"
                    @click="assignOpen = true"
                >
                    <UserPlusIcon aria-hidden="true" />
                    Ajouter un apprenti
                </Button>
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
            <SituationTiles
                v-model="filters.situation"
                :apprentices="apprentices"
                :total-label="
                    can.assignSelfAs ? 'Apprentis suivis' : 'Apprentis actifs'
                "
            />

            <section
                class="bg-card flex flex-col overflow-hidden rounded-xl border shadow-xs"
                aria-label="Liste des apprentis"
            >
                <div class="flex flex-col gap-3 border-b p-4">
                    <div
                        class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center"
                        role="group"
                        aria-label="Recherche et filtres"
                    >
                        <SearchInput
                            v-model="filters.search"
                            placeholder="Rechercher un apprenti, un coach…"
                            class="col-span-2 sm:w-72"
                        />
                        <TabFilter
                            v-if="hasTrackFilter"
                            v-model="filters.track"
                            :options="TRACK_FILTER_OPTIONS"
                            label="Filtrer par filière"
                            class="col-span-2 sm:col-span-1"
                        />
                        <FilterSelect
                            v-model="filters.year"
                            :options="YEAR_OPTIONS"
                            neutral="all"
                            label="Filtrer par année d'apprentissage"
                        />
                        <FilterSelect
                            v-model="filters.variant"
                            :options="VARIANT_OPTIONS"
                            neutral="all"
                            label="Filtrer par variante"
                        />
                        <FilterSelect
                            v-model="filters.situation"
                            :options="SITUATION_OPTIONS"
                            neutral="all"
                            label="Filtrer par situation"
                        />
                        <FilterSelect
                            v-if="can.manageSupervision"
                            v-model="filters.supervision"
                            :options="SUPERVISION_OPTIONS"
                            neutral="all"
                            label="Filtrer par suivi"
                        />
                    </div>

                    <div
                        class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2"
                    >
                        <p
                            class="text-muted-foreground flex flex-wrap items-center gap-x-2 text-sm"
                            aria-live="polite"
                        >
                            <template v-if="isFiltered">
                                <span>
                                    <span
                                        class="text-foreground font-medium tabular-nums"
                                    >
                                        {{ results.length }}
                                    </span>
                                    sur
                                    {{ plural(apprentices.length, 'apprenti') }}
                                    <template v-if="activeCount > 0">
                                        · {{ activeCount }} filtre{{
                                            activeCount > 1
                                                ? 's actifs'
                                                : ' actif'
                                        }}
                                    </template>
                                </span>
                                <Button
                                    variant="link"
                                    size="sm"
                                    class="h-auto px-0"
                                    @click="reset"
                                >
                                    <XIcon aria-hidden="true" />
                                    Effacer les filtres
                                </Button>
                            </template>
                            <template v-else>
                                {{ plural(apprentices.length, 'apprenti') }}
                            </template>
                        </p>

                        <div class="flex items-center gap-2">
                            <FilterSelect
                                v-model="filters.sort"
                                :options="SORT_OPTIONS"
                                label="Trier la liste"
                                class="w-auto"
                            >
                                <template #icon>
                                    <ArrowUpDownIcon
                                        class="text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                </template>
                            </FilterSelect>
                            <ToggleGroup
                                :model-value="layout"
                                type="single"
                                variant="outline"
                                class="hidden lg:flex"
                                aria-label="Affichage de la liste"
                                @update:model-value="selectLayout"
                            >
                                <ToggleGroupItem
                                    value="table"
                                    aria-label="Afficher en tableau"
                                    title="Tableau"
                                >
                                    <ListIcon aria-hidden="true" />
                                </ToggleGroupItem>
                                <ToggleGroupItem
                                    value="grid"
                                    aria-label="Afficher en cartes"
                                    title="Cartes"
                                >
                                    <LayoutGridIcon aria-hidden="true" />
                                </ToggleGroupItem>
                            </ToggleGroup>
                        </div>
                    </div>
                </div>

                <Empty v-if="results.length === 0" class="rounded-none">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <SearchXIcon />
                        </EmptyMedia>
                        <EmptyTitle>Aucun résultat</EmptyTitle>
                        <EmptyDescription>
                            Aucun apprenti ne correspond à ces critères.
                        </EmptyDescription>
                    </EmptyHeader>
                    <EmptyContent>
                        <Button variant="outline" size="sm" @click="reset">
                            <XIcon aria-hidden="true" />
                            Effacer les filtres
                        </Button>
                    </EmptyContent>
                </Empty>

                <template v-else>
                    <!-- Petits écrans, ou affichage « cartes » choisi. -->
                    <div
                        class="bg-muted/30 grid gap-3 p-4 sm:grid-cols-2"
                        :class="
                            layout === 'grid' ? 'lg:grid-cols-3' : 'lg:hidden'
                        "
                    >
                        <ApprenticeCard
                            v-for="apprentice in results"
                            :key="apprentice.id"
                            :apprentice="apprentice"
                            @preview="openPreview"
                        />
                    </div>

                    <DataTable
                        v-if="layout === 'table'"
                        :columns="columns"
                        :data="results"
                        class="hidden rounded-none border-0 lg:block"
                    >
                        <template #row="{ item }">
                            <ApprenticeRow
                                :apprentice="item"
                                :coaches="
                                    can.manageSupervision ? coaches : null
                                "
                                :trainers="
                                    can.manageSupervision ? trainers : null
                                "
                                @preview="openPreview"
                            />
                        </template>
                    </DataTable>

                    <p class="text-muted-foreground border-t px-4 py-3 text-xs">
                        Affichage de
                        <span class="text-foreground font-medium tabular-nums">
                            {{ results.length }}
                        </span>
                        sur {{ plural(apprentices.length, 'apprenti') }} ·
                        moyennes simples des notes saisies, seuil de réussite à
                        {{ PASSING_GRADE.toFixed(1) }}
                    </p>
                </template>
            </section>
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
