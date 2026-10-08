<script setup lang="ts">
import {
    ArrowDownRightIcon,
    ArrowUpRightIcon,
    FileTextIcon,
    GraduationCapIcon,
    TrendingDownIcon,
    TrendingUpIcon,
    TriangleAlertIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import DomainProgressList from '@/components/dashboard/DomainProgressList.vue';
import ProgressChart from '@/components/dashboard/ProgressChart.vue';
import StatTile from '@/components/dashboard/StatTile.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    PASSING_GRADE,
    type ProgressPeriod,
    type ProgressPoint,
} from '@/data/dashboard';
import { DOMAIN_GRADES, gradeTables, type DomainGrade } from '@/data/gradebook';
import { averageStatus } from '@/lib/apprentice';
import type { Grade } from '@/types/grade';
import type { RouteDefinition } from '@/wayfinder';
import GradeLinkList from './GradeLinkList.vue';

const props = defineProps<{
    /** Notes de l'apprenti·e, de la plus ancienne à la plus récente. */
    grades: Grade[];
    gradeHref: (grade: Grade) => RouteDefinition<'get'>;
}>();

/**
 * Moyennes simples des notes saisies, arrondies au dixième. Ce n'est pas la
 * moyenne officielle pondérée du CFC (pas encore calculée côté serveur).
 */
const mean = (grades: Grade[]): number | null =>
    grades.length > 0
        ? Math.round(
              (grades.reduce((sum, grade) => sum + grade.value, 0) /
                  grades.length) *
                  10,
          ) / 10
        : null;

const average = computed(() => mean(props.grades));

const SEMESTER_PERIODS: ProgressPeriod[] = Array.from(
    { length: 8 },
    (_, i) => ({
        position: i + 1,
        tick: `S${i + 1}`,
        title: `Semestre ${i + 1}`,
    }),
);

/** Une moyenne par semestre ayant au moins une note. */
const semesterPoints = computed<ProgressPoint[]>(() =>
    SEMESTER_PERIODS.flatMap(({ position, title }) => {
        const value = mean(
            props.grades.filter((grade) => grade.semester === position),
        );

        return value === null ? [] : [{ position, title, average: value }];
    }),
);

/** Écart entre les deux derniers semestres notés. */
const trend = computed(() => {
    const [previous, current] = semesterPoints.value.slice(-2);

    return previous && current
        ? { delta: current.average - previous.average, since: previous.title }
        : null;
});

/** Moyenne de chaque domaine noté, avec son poids dans le CFC. */
const domains = computed<DomainGrade[]>(() =>
    gradeTables(props.grades).flatMap((menu) => {
        const value = mean(menu.grades);

        return value === null
            ? []
            : [
                  {
                      title: menu.title,
                      weight:
                          DOMAIN_GRADES.find(
                              (domain) => domain.title === menu.title,
                          )?.weight ?? '',
                      grade: value,
                  },
              ];
    }),
);

const insufficient = computed(() =>
    props.grades.filter((grade) => grade.value < PASSING_GRADE),
);

const recentGrades = computed(() => props.grades.slice(-5).reverse());
</script>

<template>
    <section
        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        aria-label="Indicateurs"
    >
        <StatTile
            label="Moyenne des notes"
            :value="average !== null ? average.toFixed(1) : '—'"
            :hint="
                average === null
                    ? 'Aucune note pour l’instant'
                    : `Appréciation ${averageStatus(average).label.toLowerCase()}`
            "
            :icon="GraduationCapIcon"
            :tone="average === null ? 'muted' : averageStatus(average).tone"
        />
        <StatTile
            label="Notes saisies"
            :value="String(grades.length)"
            :hint="
                semesterPoints.length > 0
                    ? `Sur ${semesterPoints.length} semestre${semesterPoints.length > 1 ? 's' : ''}`
                    : undefined
            "
            :icon="FileTextIcon"
        />
        <StatTile
            label="Tendance"
            :value="
                trend
                    ? `${trend.delta >= 0 ? '+' : ''}${trend.delta.toFixed(1)}`
                    : '—'
            "
            :hint="
                trend
                    ? `Par rapport au ${trend.since.toLowerCase()}`
                    : 'Il faut deux semestres notés'
            "
            :icon="trend && trend.delta < 0 ? TrendingDownIcon : TrendingUpIcon"
            :tone="
                trend === null
                    ? 'muted'
                    : trend.delta < 0
                      ? 'destructive'
                      : 'success'
            "
        >
            <template v-if="trend" #badge>
                <span
                    class="flex items-center gap-0.5 rounded-md px-1.5 py-0.5 text-xs font-medium"
                    :class="
                        trend.delta < 0
                            ? 'bg-destructive/10 text-destructive'
                            : 'bg-success/15 text-success'
                    "
                >
                    <component
                        :is="
                            trend.delta < 0
                                ? ArrowDownRightIcon
                                : ArrowUpRightIcon
                        "
                        class="size-3.5"
                        aria-hidden="true"
                    />
                    {{ trend.delta < 0 ? 'En baisse' : 'En hausse' }}
                </span>
            </template>
        </StatTile>
        <StatTile
            label="Notes insuffisantes"
            :value="String(insufficient.length)"
            :hint="`Notes sous ${PASSING_GRADE.toFixed(1)}`"
            :icon="TriangleAlertIcon"
            :tone="insufficient.length > 0 ? 'destructive' : 'muted'"
        />
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
        <Card class="shadow-xs lg:col-span-2">
            <CardHeader>
                <CardTitle>Progression</CardTitle>
                <CardDescription>
                    Moyenne des notes de chaque semestre du cursus
                </CardDescription>
            </CardHeader>
            <CardContent>
                <ProgressChart
                    :data="semesterPoints"
                    :periods="SEMESTER_PERIODS"
                    caption="Moyenne des notes par semestre"
                />
            </CardContent>
        </Card>

        <Card class="shadow-xs">
            <CardHeader>
                <CardTitle>Domaines</CardTitle>
                <CardDescription>Moyenne et poids dans le CFC</CardDescription>
            </CardHeader>
            <CardContent>
                <DomainProgressList v-if="domains.length" :domains="domains" />
                <p v-else class="text-muted-foreground text-sm">
                    Aucun domaine noté pour l'instant.
                </p>
            </CardContent>
        </Card>
    </section>

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
</template>
