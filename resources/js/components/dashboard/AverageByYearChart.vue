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
import { PASSING_GRADE, type YearAverage } from '@/data/dashboard';

const props = defineProps<{
    data: YearAverage[];
}>();

const chartConfig = {
    average: { label: 'Moyenne', color: 'var(--chart-1)' },
    count: { label: 'Apprentis', color: 'var(--muted-foreground)' },
} satisfies ChartConfig;

const x = (d: YearAverage) => d.year;
const y = [(d: YearAverage) => d.average];

const yearLabel = (year: number | Date) =>
    props.data.find((d) => d.year === year)?.label ?? String(year);

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
                    :group-max-width="56"
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
                    :tick-format="yearLabel"
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
                            labelFormatter: (year) =>
                                `${yearLabel(year)} année`,
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
            Moyenne des apprentis par année d'apprentissage
        </caption>
        <thead>
            <tr>
                <th scope="col">Année</th>
                <th scope="col">Moyenne</th>
                <th scope="col">Apprentis</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="row in data" :key="row.year">
                <td>{{ row.label }} année</td>
                <td>{{ row.average.toFixed(1) }}</td>
                <td>{{ row.count }}</td>
            </tr>
        </tbody>
    </table>
</template>
