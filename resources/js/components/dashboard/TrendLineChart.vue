<script setup lang="ts">
import { VisArea, VisAxis, VisLine, VisXYContainer } from '@unovis/vue';
import type { ChartConfig } from '@/components/ui/chart';
import {
    ChartContainer,
    ChartCrosshair,
    ChartTooltip,
    ChartTooltipContent,
    componentToString,
} from '@/components/ui/chart';
import type { GradeScale, TimelinePoint } from '@/types/dashboard';

const props = defineProps<{
    points: TimelinePoint[];
    scale: GradeScale;
}>();

/**
 * Une seule série : pas de légende, le titre de la carte la nomme. Les teintes
 * sombres sont choisies pour la surface sombre, pas dérivées des claires.
 */
const chartConfig = {
    average: {
        label: 'Moyenne',
        theme: { light: '#2a78d6', dark: '#3987e5' },
    },
    threshold: {
        label: 'Seuil de réussite',
        theme: { light: '#cbd5e1', dark: '#334155' },
    },
} satisfies ChartConfig;

const x = (_point: TimelinePoint, index: number) => index;
const y = (point: TimelinePoint) => point.average;
const thresholdY = () => props.scale.passing;

const tooltipTemplate = componentToString(chartConfig, ChartTooltipContent, {
    labelFormatter: (index) => props.points[Number(index)]?.label ?? '',
});
</script>

<template>
    <ChartContainer :config="chartConfig" class="h-56 w-full">
        <VisXYContainer
            :data="points"
            :y-domain="[scale.min, scale.max]"
            :margin="{ top: 8, right: 8, bottom: 4, left: 4 }"
        >
            <!-- Seuil de réussite, en retrait : c'est du repère, pas une série. -->
            <VisLine
                :x="x"
                :y="thresholdY"
                color="var(--color-threshold)"
                :line-width="1"
            />

            <VisArea
                :x="x"
                :y="y"
                color="var(--color-average)"
                :opacity="0.1"
            />

            <VisLine
                :x="x"
                :y="y"
                color="var(--color-average)"
                :line-width="2"
            />

            <VisAxis
                type="x"
                :tick-format="(index: number) => points[index]?.label ?? ''"
                :grid-line="false"
                :tick-line="false"
                :num-ticks="points.length"
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
                color="var(--color-average)"
                :template="tooltipTemplate"
            />
        </VisXYContainer>
    </ChartContainer>
</template>
