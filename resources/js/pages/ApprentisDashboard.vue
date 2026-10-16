<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    ArrowUpDownIcon,
    SearchXIcon,
    SlidersHorizontalIcon,
    UserPlusIcon,
    UsersIcon,
    XIcon,
} from '@lucide/vue';
import { computed, ref, toRef } from 'vue';
import ApprenticeCard from '@/components/apprentice/ApprenticeCard.vue';
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
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    SORT_OPTIONS,
    SUPERVISION_OPTIONS,
    useApprenticeFilters,
    VARIANT_OPTIONS,
    YEAR_OPTIONS,
} from '@/composables/useApprenticeFilters';
import { TRACK_FILTER_OPTIONS } from '@/constants/constants';
import { PASSING_GRADE } from '@/lib/grade-scale';
import type {
    ApprenticeListItem,
    AssignableApprentice,
    SupervisorOption,
} from '@/types/apprentice';

const props = defineProps<{
    /**
     * Apprentis actifs suivis (`User::listedApprentices()`) : coach → ses
     * coachés, formateur → les apprentis des plans qu'il forme, admin → tous.
     */
    apprentices: ApprenticeListItem[];
    /** Proposés par « Ajouter un apprenti ». */
    assignable: AssignableApprentice[];
    coaches: SupervisorOption[];
    can: {
        /** Coach : s'ajoute des apprentis, dont il devient le coach. */
        assignSelf: boolean;
        /** Admin local : choix du coach de chaque apprenti·e. */
        manageSupervision: boolean;
    };
}>();

const { filters, results, hasTrackFilter, isFiltered, reset } =
    useApprenticeFilters(toRef(props, 'apprentices'));

const plural = (count: number, word: string): string =>
    `${count} ${word}${count > 1 ? 's' : ''}`;

/** Le nombre d'apprentis est affiché au-dessus de la liste, pas ici. */
const description = computed(() => {
    const available = props.assignable.length;

    return props.can.assignSelf && available > 0
        ? `${plural(available, 'apprenti')} sans coach à ajouter`
        : undefined;
});

/** Filtres secondaires, rangés derrière le bouton « Filtres ». */
const moreFilterCount = computed(
    () =>
        [
            filters.year !== 'all',
            filters.variant !== 'all',
            filters.supervision !== 'all',
        ].filter(Boolean).length,
);

const assignOpen = ref(false);

/**
 * Un coach ne voit que ses coachés : la colonne Coach répéterait son nom sur
 * chaque ligne.
 */
const columns = computed(() =>
    [
        { key: 'apprentice', label: 'Apprenti·e' },
        { key: 'track', label: 'Filière' },
        { key: 'average', label: 'Moyenne' },
        { key: 'activity', label: 'Activité' },
        { key: 'coach', label: 'Coach' },
        { key: 'trainer', label: 'Formateur' },
    ].filter(({ key }) => key !== 'coach' || !props.can.assignSelf),
);
</script>

<template>
    <Head title="Apprentis" />

    <PageContainer size="lg">
        <PageHeader
            :title="can.assignSelf ? 'Mes apprentis' : 'Apprentis'"
            :description="description"
        >
            <template v-if="can.assignSelf" #actions>
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
                        can.assignSelf
                            ? 'Vous ne suivez encore aucun apprenti'
                            : 'Aucun apprenti pour le moment'
                    }}
                </EmptyTitle>
                <EmptyDescription>
                    {{
                        can.assignSelf
                            ? 'Ajoutez les apprentis que vous suivez pour voir leurs notes et leur portfolio.'
                            : 'Les apprentis actifs apparaîtront ici dès leur synchronisation depuis Entra.'
                    }}
                </EmptyDescription>
            </EmptyHeader>
            <EmptyContent v-if="can.assignSelf">
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
                    can.assignSelf ? 'Apprentis suivis' : 'Apprentis actifs'
                "
            />

            <section
                class="bg-card flex flex-col overflow-hidden rounded-xl border shadow-xs"
                aria-label="Liste des apprentis"
            >
                <!-- Recherche et tri ; la situation se filtre avec les tuiles. -->
                <div
                    class="flex flex-wrap items-center gap-2 border-b p-4"
                    role="group"
                    aria-label="Recherche et filtres"
                >
                    <SearchInput
                        v-model="filters.search"
                        placeholder="Rechercher un apprenti, un coach…"
                        class="w-full sm:w-72"
                    />
                    <TabFilter
                        v-if="hasTrackFilter"
                        v-model="filters.track"
                        :options="TRACK_FILTER_OPTIONS"
                        label="Filtrer par filière"
                    />
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                :class="
                                    moreFilterCount > 0 &&
                                    'border-primary text-primary'
                                "
                            >
                                <SlidersHorizontalIcon aria-hidden="true" />
                                Filtres
                                <span
                                    v-if="moreFilterCount > 0"
                                    class="tabular-nums"
                                >
                                    ({{ moreFilterCount }})
                                </span>
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent
                            align="start"
                            class="flex w-64 flex-col gap-2"
                        >
                            <FilterSelect
                                v-model="filters.year"
                                :options="YEAR_OPTIONS"
                                neutral="all"
                                label="Filtrer par année d'apprentissage"
                                class="sm:w-full"
                            />
                            <FilterSelect
                                v-model="filters.variant"
                                :options="VARIANT_OPTIONS"
                                neutral="all"
                                label="Filtrer par variante"
                                class="sm:w-full"
                            />
                            <FilterSelect
                                v-if="can.manageSupervision"
                                v-model="filters.supervision"
                                :options="SUPERVISION_OPTIONS"
                                neutral="all"
                                label="Filtrer par suivi"
                                class="sm:w-full"
                            />
                        </PopoverContent>
                    </Popover>

                    <FilterSelect
                        v-model="filters.sort"
                        :options="SORT_OPTIONS"
                        label="Trier la liste"
                        class="w-auto sm:ml-auto"
                    >
                        <template #icon>
                            <ArrowUpDownIcon
                                class="text-muted-foreground"
                                aria-hidden="true"
                            />
                        </template>
                    </FilterSelect>

                    <p
                        class="text-muted-foreground flex w-full flex-wrap items-center gap-x-2 text-sm"
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
                    <!-- Cartes sur petit écran, tableau sur grand écran. -->
                    <div
                        class="bg-muted/30 grid gap-3 p-4 sm:grid-cols-2 lg:hidden"
                    >
                        <ApprenticeCard
                            v-for="apprentice in results"
                            :key="apprentice.id"
                            :apprentice="apprentice"
                            :hide-coach="can.assignSelf"
                        />
                    </div>

                    <DataTable
                        :columns="columns"
                        :data="results"
                        class="hidden rounded-none border-0 lg:block"
                    >
                        <template #row="{ item }">
                            <ApprenticeRow
                                :apprentice="item"
                                :hide-coach="can.assignSelf"
                                :coaches="
                                    can.manageSupervision ? coaches : null
                                "
                            />
                        </template>
                    </DataTable>

                    <p class="text-muted-foreground border-t px-4 py-3 text-xs">
                        Moyennes simples des notes saisies, seuil de réussite à
                        {{ PASSING_GRADE.toFixed(1) }}
                    </p>
                </template>
            </section>
        </template>

        <AssignApprenticeDialog
            v-if="can.assignSelf"
            v-model:open="assignOpen"
            :assignable="assignable"
        />
    </PageContainer>
</template>
