<script setup lang="ts">
import {
    CircleDashedIcon,
    ClockIcon,
    TrendingDownIcon,
    UsersIcon,
} from '@lucide/vue';
import { computed, type Component } from 'vue';
import IconTile from '@/components/IconTile.vue';
import {
    matchesSituation,
    type SituationFilter,
} from '@/composables/useApprenticeFilters';
import { PASSING_GRADE } from '@/data/dashboard';
import { STALE_AFTER_DAYS, TONES, type Tone } from '@/lib/apprentice';
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
    /** Couleur de l'icône et de la jauge ; la valeur la prend si non nulle. */
    tone: Tone;
};

const TILES: Tile[] = [
    {
        value: 'all',
        label: '',
        hint: 'Toute la liste',
        icon: UsersIcon,
        tone: 'primary',
    },
    {
        value: 'insufficient',
        label: 'Moyenne insuffisante',
        hint: `En dessous de ${PASSING_GRADE.toFixed(1)}`,
        icon: TrendingDownIcon,
        tone: 'destructive',
    },
    {
        value: 'stale',
        label: 'Sans note récente',
        hint: `Rien depuis ${STALE_AFTER_DAYS} jours`,
        icon: ClockIcon,
        tone: 'warning',
    },
    {
        value: 'no-grades',
        label: 'Aucune note',
        hint: 'Carnet encore vide',
        icon: CircleDashedIcon,
        tone: 'muted',
    },
];

const tiles = computed(() =>
    TILES.map((tile) => {
        const count = props.apprentices.filter((apprentice) =>
            matchesSituation(apprentice, tile.value),
        ).length;
        const total = props.apprentices.length;

        return {
            ...tile,
            label: tile.value === 'all' ? props.totalLabel : tile.label,
            count,
            /** Part de la liste, en pour cent arrondi. */
            share: total > 0 ? Math.round((count / total) * 100) : 0,
        };
    }),
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
                    'bg-card group/tile focus-visible:ring-ring/50 flex flex-col gap-4 rounded-xl border p-4 text-left shadow-xs transition-all hover:-translate-y-0.5 hover:shadow-md focus-visible:ring-[3px] focus-visible:outline-none',
                    tile.value !== 'all' &&
                        situation === tile.value &&
                        'border-primary ring-primary/15 ring-2',
                )
            "
            @click="select(tile.value)"
        >
            <span class="flex items-start justify-between gap-2">
                <IconTile :class="TONES[tile.tone].text">
                    <component :is="tile.icon" />
                </IconTile>
                <span
                    v-if="tile.value !== 'all'"
                    class="text-muted-foreground bg-muted rounded-md px-1.5 py-0.5 text-xs font-medium tabular-nums"
                    :title="`${tile.share} % de la liste`"
                >
                    {{ tile.share }} %
                </span>
            </span>

            <span class="flex flex-col gap-0.5">
                <span
                    class="text-2xl font-semibold tracking-tight tabular-nums"
                    :class="
                        tile.count > 0 && tile.value !== 'all'
                            ? TONES[tile.tone].text
                            : undefined
                    "
                >
                    {{ tile.count }}
                </span>
                <span class="text-sm font-medium">{{ tile.label }}</span>
                <span class="text-muted-foreground text-xs">
                    {{ tile.hint }}
                </span>
            </span>

            <!-- Part de la liste concernée. -->
            <span
                class="bg-muted h-1 w-full overflow-hidden rounded-full"
                aria-hidden="true"
            >
                <span
                    class="block h-full rounded-full transition-[width]"
                    :class="TONES[tile.tone].bar"
                    :style="{
                        width: `${tile.value === 'all' ? 100 : tile.share}%`,
                    }"
                />
            </span>
        </button>
    </div>
</template>
