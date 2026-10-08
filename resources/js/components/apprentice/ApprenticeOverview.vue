<script setup lang="ts">
import { computed } from 'vue';
import DomainChart from '@/components/gradebook/DomainChart.vue';
import HatchPattern from '@/components/gradebook/HatchPattern.vue';
import SemesterChart from '@/components/gradebook/SemesterChart.vue';
import StatTiles from '@/components/gradebook/StatTiles.vue';
import TrainingPath from '@/components/gradebook/TrainingPath.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { PASSING_GRADE } from '@/data/dashboard';
import {
    byDateDesc,
    type Gradebook,
    lastGradedSemester,
} from '@/lib/gradebook';
import type { Apprentice } from '@/types/apprentice';
import type { Grade } from '@/types/grade';
import type { RouteDefinition } from '@/wayfinder';
import GradeLinkList from './GradeLinkList.vue';

/**
 * Vue d'ensemble du profil : les mêmes indicateurs que l'accueil de
 * l'apprenti·e, calculés sur son arbre (moyennes pondérées du CFC).
 */
const props = defineProps<{
    apprentice: Apprentice;
    gradebook: Gradebook;
    grades: Grade[];
    gradeHref: (grade: Grade) => RouteDefinition<'get'>;
}>();

const currentSemester = computed(() => lastGradedSemester(props.grades));

const profile = computed(() => ({
    track: props.apprentice.track,
    variant: props.apprentice.isMp ? ('mp' as const) : ('standard' as const),
}));

const insufficient = computed(() =>
    props.grades
        .filter((grade) => grade.value < PASSING_GRADE)
        .sort(byDateDesc),
);

const recentGrades = computed(() =>
    [...props.grades].sort(byDateDesc).slice(0, 5),
);
</script>

<template>
    <TrainingPath
        :gradebook="gradebook"
        :current-semester="currentSemester"
        :profile="profile"
        derived
    />

    <StatTiles :gradebook="gradebook" :grades="grades" />

    <div class="chart-grid">
        <SemesterChart
            :gradebook="gradebook"
            :current-semester="currentSemester"
        />
        <DomainChart :gradebook="gradebook" />
    </div>

    <section class="grid gap-4 lg:grid-cols-2">
        <Card class="shadow-xs">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    À surveiller
                    <span
                        v-if="insufficient.length"
                        class="bg-destructive/10 text-destructive rounded-md px-1.5 py-0.5 text-xs font-medium tabular-nums"
                    >
                        {{ insufficient.length }}
                    </span>
                </CardTitle>
                <CardDescription>
                    Notes sous {{ PASSING_GRADE.toFixed(1) }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <GradeLinkList
                    :grades="insufficient"
                    :grade-href="gradeHref"
                    empty="Aucune note insuffisante."
                />
            </CardContent>
        </Card>

        <Card class="shadow-xs">
            <CardHeader>
                <CardTitle>Dernières notes</CardTitle>
                <CardDescription>Les 5 notes les plus récentes</CardDescription>
            </CardHeader>
            <CardContent>
                <GradeLinkList
                    :grades="recentGrades"
                    :grade-href="gradeHref"
                    empty="Aucune note pour l'instant."
                />
            </CardContent>
        </Card>
    </section>

    <HatchPattern />
</template>
