<script setup lang="ts">
import {
    VisAxis,
    VisGroupedBar,
    VisPlotline,
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
import { PASSING_GRADE } from '@/data/dashboard';

export interface ApprenticeAverage {
    /** Position sur l'axe, à partir de 1. */
    position: number;
    name: string;
    average: number;
}

const props = defineProps<{
    data: ApprenticeAverage[];
}>();

const chartConfig = {
    average: { label: 'Moyenne', color: 'var(--chart-1)' },
} satisfies ChartConfig;

const x = (d: ApprenticeAverage) => d.position;
const y = [(d: ApprenticeAverage) => d.average];

const nameAt = (position: number | Date) =>
    props.data.find((d) => d.position === position)?.name ?? '';

/** Prénom sous la barre : le nom complet est dans l'infobulle. */
const tickLabel = (position: number | Date) => nameAt(position).split(/\s+/)[0];

// Unovis dessine dans le DOM : rien à rendre côté serveur (SSR).
const isMounted = useMounted();
</script>

<template>
    <div class="h-56">
        <ChartContainer
            v-if="isMounted"
            :config="chartConfig"
            class="aspect-auto h-full"
        >
            <!-- Barres ancrées à 0 : leur longueur reste proportionnelle à la moyenne. -->
            <VisXYContainer
                :data="data"
                :y-domain="[0, 6]"
                :margin="{ top: 8, right: 16 }"
            >
                <VisGroupedBar
                    :x="x"
                    :y="y"
                    :color="chartConfig.average.color"
                    :rounded-corners="4"
                    :group-max-width="40"
                />
                <VisPlotline
                    axis="y"
                    :value="PASSING_GRADE"
                    line-style="dash"
                    :line-width="1"
                    color="var(--muted-foreground)"
                />
                <VisAxis
                    type="x"
                    :tick-values="data.map(x)"
                    :tick-format="tickLabel"
                    :grid-line="false"
                    :domain-line="false"
                    :tick-line="false"
                />
                <VisAxis
                    type="y"
                    :tick-values="[0, 2, 4, 6]"
                    :domain-line="false"
                    :tick-line="false"
                />
                <ChartTooltip />
                <ChartCrosshair
                    :x="x"
                    :y="y"
                    :template="
                        componentToString(chartConfig, ChartTooltipContent, {
                            labelFormatter: nameAt,
                        })
                    "
                    color="#0000"
                />
            </VisXYContainer>
        </ChartContainer>
        <div v-else class="bg-muted/50 h-full animate-pulse rounded-lg" />
    </div>

    <table class="sr-only">
        <caption>
            Moyenne des notes de chaque apprenti·e suivi·e
        </caption>
        <thead>
            <tr>
                <th scope="col">Apprenti·e</th>
                <th scope="col">Moyenne</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="row in data" :key="row.position">
                <td>{{ row.name }}</td>
                <td>{{ row.average.toFixed(1) }}</td>
            </tr>
        </tbody>
    </table>
</template>
