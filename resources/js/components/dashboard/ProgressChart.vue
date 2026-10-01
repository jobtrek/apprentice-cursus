<script setup lang="ts">
import {
    VisAxis,
    VisLine,
    VisPlotline,
    VisScatter,
    VisXYContainer,
} from '@unovis/vue';
import { useMounted } from '@vueuse/core';
import {
    ChartContainer,
    ChartCrosshair,
    ChartTooltip,
    ChartTooltipContent,
    componentToString,
    type ChartConfig,
} from '@/components/ui/chart';
import {
    PASSING_GRADE,
    type ProgressPeriod,
    type ProgressPoint,
} from '@/data/dashboard';

const props = defineProps<{
    /** Moyennes connues, placées sur `periods` par leur `position`. */
    data: ProgressPoint[];
    /**
     * Toutes les périodes du cursus, affichées même sans moyenne : la
     * progression se lit sur l'ensemble de la formation.
     */
    periods: ProgressPeriod[];
    /** Titre du tableau de données lu par les lecteurs d'écran. */
    caption: string;
}>();

const chartConfig = {
    average: { label: 'Moyenne', color: 'var(--chart-1)' },
} satisfies ChartConfig;

const x = (d: ProgressPoint) => d.position;
const y = (d: ProgressPoint) => d.average;

/**
 * « Line Chart - Dots Colors » (shadcn) : chaque point prend la couleur de son
 * niveau, la ligne garde la couleur de la série.
 */
const LEVELS = [
    { label: 'Insuffisant (< 4)', color: 'var(--chart-5)', min: 0 },
    { label: 'Suffisant (4 – 5)', color: 'var(--chart-3)', min: PASSING_GRADE },
    { label: 'Bon (≥ 5)', color: 'var(--chart-2)', min: 5 },
] as const;

const dotColor = (d: ProgressPoint) =>
    LEVELS.findLast((level) => d.average >= level.min)?.color ??
    LEVELS[0].color;

const periodAt = (position: number | Date) =>
    props.periods.find((period) => period.position === position);

// Créé une seule fois : le libellé est relu dans `periods` à chaque survol.
// Le rendu est mis en cache par point ; `title` dans chaque point évite de
// réutiliser le libellé d'une autre vue (semestres ↔ années).
const tooltip = componentToString(chartConfig, ChartTooltipContent, {
    labelFormatter: (position) => periodAt(position)?.title ?? '',
});

// Unovis dessine dans le DOM : rien à rendre côté serveur (SSR).
const isMounted = useMounted();
</script>

<template>
    <div class="h-56">
        <ChartContainer
            v-if="isMounted"
            :config="chartConfig"
            class="aspect-auto h-full"
            cursor
        >
            <!-- Recréé quand le nombre de périodes change (semestres ↔ années). -->
            <VisXYContainer
                :key="periods.length"
                :data="data"
                :x-domain="[1, periods.length]"
                :y-domain="[1, 6]"
                :margin="{ top: 8, right: 16 }"
            >
                <VisPlotline
                    axis="y"
                    :value="PASSING_GRADE"
                    line-style="dash"
                    :line-width="1"
                    color="var(--muted-foreground)"
                    label-text="Seuil 4.0"
                    label-position="top-right"
                    label-color="var(--muted-foreground)"
                    :label-size="11"
                />
                <VisLine
                    :x="x"
                    :y="y"
                    :color="chartConfig.average.color"
                    :line-width="2"
                />
                <VisScatter
                    :x="x"
                    :y="y"
                    :size="12"
                    :color="dotColor"
                    stroke-color="var(--card)"
                    :stroke-width="2"
                />
                <VisAxis
                    type="x"
                    :tick-values="periods.map((period) => period.position)"
                    :tick-format="
                        (position: number) => periodAt(position)?.tick ?? ''
                    "
                    :grid-line="false"
                    :domain-line="false"
                    :tick-line="false"
                />
                <VisAxis
                    type="y"
                    :tick-values="[1, 2, 3, 4, 5, 6]"
                    :domain-line="false"
                    :tick-line="false"
                />
                <ChartTooltip />
                <ChartCrosshair
                    :x="x"
                    :y="y"
                    :template="tooltip"
                    :color="dotColor"
                />
            </VisXYContainer>
        </ChartContainer>
        <div v-else class="bg-muted/50 h-full animate-pulse rounded-lg" />
    </div>

    <ul
        class="text-muted-foreground mt-3 flex flex-wrap justify-center gap-x-4 gap-y-1 text-xs"
        aria-hidden="true"
    >
        <li
            v-for="level in LEVELS"
            :key="level.label"
            class="flex items-center gap-1.5"
        >
            <span
                class="size-2.5 rounded-full"
                :style="{ backgroundColor: level.color }"
            />
            {{ level.label }}
        </li>
    </ul>

    <table class="sr-only">
        <caption>
            {{
                caption
            }}
        </caption>
        <thead>
            <tr>
                <th scope="col">Période</th>
                <th scope="col">Moyenne</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="point in data" :key="point.position">
                <td>{{ point.title }}</td>
                <td>{{ point.average.toFixed(1) }}</td>
            </tr>
        </tbody>
    </table>
</template>
