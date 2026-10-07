<script setup lang="ts">
import {
    CircleDashedIcon,
    ClockIcon,
    TrendingDownIcon,
    UsersIcon,
} from '@lucide/vue';
import { computed, type Component } from 'vue';
import {
    matchesSituation,
    type SituationFilter,
} from '@/composables/useApprenticeFilters';
import { PASSING_GRADE } from '@/data/dashboard';
import { STALE_AFTER_DAYS } from '@/lib/apprentice';
import { cn } from '@/lib/utils';
import type { ApprenticeListItem } from '@/types/apprentice';

const props = defineProps<{
    /** Toute la liste, avant filtres : les compteurs ne bougent pas. */
    apprentices: ApprenticeListItem[];
    /** Libellé de la tuile « tous », ex. « Apprentis suivis ». */
    totalLabel: string;
}>();

/** Filtre de situation : une tuile cliquée l'applique, recliquée le retire. */
const situation = defineModel<SituationFilter>({ required: true });

type Tile = {
    value: SituationFilter;
    label: string;
    hint: string;
    icon: Component;
    /** Couleur de la valeur quand elle n'est pas nulle. */
    tone?: string;
};

const TILES: Tile[] = [
    {
        value: 'all',
        label: '',
        hint: 'Toute la liste',
        icon: UsersIcon,
    },
    {
        value: 'insufficient',
        label: 'Moyenne insuffisante',
        hint: `En dessous de ${PASSING_GRADE.toFixed(1)}`,
        icon: TrendingDownIcon,
        tone: 'text-destructive',
    },
    {
        value: 'stale',
        label: 'Sans note récente',
        hint: `Rien depuis ${STALE_AFTER_DAYS} jours`,
        icon: ClockIcon,
        tone: 'text-warning',
    },
    {
        value: 'no-grades',
        label: 'Aucune note',
        hint: 'Carnet encore vide',
        icon: CircleDashedIcon,
    },
];

const tiles = computed(() =>
    TILES.map((tile) => ({
        ...tile,
        label: tile.value === 'all' ? props.totalLabel : tile.label,
        count: props.apprentices.filter((apprentice) =>
            matchesSituation(apprentice, tile.value),
        ).length,
    })),
);

const select = (value: SituationFilter): void => {
    situation.value =
        situation.value === value && value !== 'all' ? 'all' : value;
};
</script>

<template>
    <div
        class="grid grid-cols-2 gap-3 lg:grid-cols-4"
        role="group"
        aria-label="Filtrer par situation"
    >
        <button
            v-for="tile in tiles"
            :key="tile.value"
            type="button"
            :aria-pressed="tile.value !== 'all' && situation === tile.value"
            :class="
                cn(
                    'bg-card hover:bg-muted/40 focus-visible:ring-ring/50 flex flex-col gap-1 rounded-xl border p-4 text-left transition-colors focus-visible:ring-[3px] focus-visible:outline-none',
                    tile.value !== 'all' &&
                        situation === tile.value &&
                        'border-primary ring-primary/15 ring-2',
                )
            "
            @click="select(tile.value)"
        >
            <span class="flex items-center justify-between gap-2">
                <span class="text-muted-foreground text-sm font-medium">
                    {{ tile.label }}
                </span>
                <component
                    :is="tile.icon"
                    class="text-muted-foreground size-4 shrink-0"
                    aria-hidden="true"
                />
            </span>
            <span
                class="text-2xl font-semibold tabular-nums"
                :class="tile.count > 0 && tile.tone"
            >
                {{ tile.count }}
            </span>
            <span class="text-muted-foreground text-xs">{{ tile.hint }}</span>
        </button>
    </div>
</template>
