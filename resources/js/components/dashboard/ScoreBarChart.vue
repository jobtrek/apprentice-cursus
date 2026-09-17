<script setup lang="ts">
import { VisAxis, VisGroupedBar, VisLine, VisXYContainer } from '@unovis/vue';
import type { ChartConfig } from '@/components/ui/chart';
import {
    ChartContainer,
    ChartCrosshair,
    ChartTooltip,
    ChartTooltipContent,
    componentToString,
} from '@/components/ui/chart';
import type { GradeScale } from '@/types/dashboard';

export type BarItem = {
    id: string;
    /** Nom complet, affiché dans l'infobulle. */
    name: string;
    /** Libellé court, affiché sous la barre. */
    short: string;
    score: number;
};

const props = defineProps<{
    items: BarItem[];
    scale: GradeScale;
}>();

/**
 * Une seule teinte pour toutes les barres : la longueur porte déjà la valeur,
 * colorer par rang doublerait l'encodage sans rien ajouter.
 */
const chartConfig = {
    score: {
        label: 'Note',
        theme: { light: '#2a78d6', dark: '#3987e5' },
    },
    threshold: {
        label: 'Seuil de réussite',
        theme: { light: '#cbd5e1', dark: '#334155' },
    },
} satisfies ChartConfig;

const x = (_item: BarItem, index: number) => index;
const y = (item: BarItem) => item.score;
const thresholdY = () => props.scale.passing;

const tooltipTemplate = componentToString(chartConfig, ChartTooltipContent, {
    labelFormatter: (index) => props.items[Number(index)]?.name ?? '',
});
</script>

<template>
    <ChartContainer :config="chartConfig" class="h-56 w-full">
        <VisXYContainer
            :data="items"
            :y-domain="[scale.min, scale.max]"
            :margin="{ top: 8, right: 8, bottom: 4, left: 4 }"
        >
            <VisGroupedBar
                :x="x"
                :y="y"
                color="var(--color-score)"
                :rounded-corners="4"
                :bar-max-width="24"
                :bar-padding="0.25"
            />

            <VisLine
                :x="x"
                :y="thresholdY"
                color="var(--color-threshold)"
                :line-width="1"
            />

            <VisAxis
                type="x"
                :tick-format="(index: number) => items[index]?.short ?? ''"
                :grid-line="false"
                :tick-line="false"
                :num-ticks="items.length"
            />

            <VisAxis
                type="y"
                :tick-format="(value: number) => value.toFixed(0)"
                :tick-line="false"
                :domain-line="false"
                :num-ticks="4"
            />

            <ChartTooltip />
            <ChartCrosshair
                color="var(--color-score)"
                :template="tooltipTemplate"
            />
        </VisXYContainer>
    </ChartContainer>
</template>
