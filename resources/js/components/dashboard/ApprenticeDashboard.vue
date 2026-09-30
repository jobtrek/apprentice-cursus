<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    CalendarIcon,
    ChevronRightIcon,
    FileTextIcon,
    GraduationCapIcon,
    TrendingDownIcon,
    TrendingUpIcon,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import DomainProgressList from '@/components/dashboard/DomainProgressList.vue';
import ProgressChart from '@/components/dashboard/ProgressChart.vue';
import StatTile from '@/components/dashboard/StatTile.vue';
import TabFilter from '@/components/TabFilter.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DEMO_APPRENTICESHIP_START_YEAR,
    GRADES_THIS_SEMESTER,
    SEMESTER_AVERAGES,
    type ProgressPeriod,
    type ProgressPoint,
} from '@/data/dashboard';
import { DOMAIN_GRADES, FINAL_GRADE, recentGrades } from '@/data/gradebook';
import { trainingPeriod } from '@/lib/semester';
import grades from '@/routes/grades';

const period = trainingPeriod(DEMO_APPRENTICESHIP_START_YEAR);

/** Écart entre les deux derniers semestres, pour la tuile « Tendance ». */
const trend = computed(() => {
    const [previous, current] = SEMESTER_AVERAGES.slice(-2);

    return previous && current
        ? {
              delta: current.average - previous.average,
              since: previous.semester,
          }
        : null;
});

const latestGrades = recentGrades(4);

type ProgressView = 'semester' | 'year';

const PROGRESS_VIEWS = [
    { label: 'Semestres', value: 'semester' },
    { label: 'Années', value: 'year' },
] as const;

const progressView = ref<ProgressView>('semester');

const SEMESTER_PERIODS: ProgressPeriod[] = Array.from(
    { length: 8 },
    (_, i) => ({
        position: i + 1,
        tick: `S${i + 1}`,
        title: `Semestre ${i + 1}`,
    }),
);

const YEAR_PERIODS: ProgressPeriod[] = ['1re', '2e', '3e', '4e'].map(
    (tick, i) => ({ position: i + 1, tick, title: `${tick} année` }),
);

/** Moyenne d'une année = moyenne de ses semestres connus (S1–S2 pour la 1re…). */
const yearPoints = (): ProgressPoint[] =>
    YEAR_PERIODS.flatMap(({ position, title }) => {
        const semesters = SEMESTER_AVERAGES.filter(
            (entry) => Math.ceil(entry.semester / 2) === position,
        );

        return semesters.length
            ? [
                  {
                      position,
                      title,
                      // Arrondie au dixième, comme toutes les moyennes affichées.
                      average:
                          Math.round(
                              (semesters.reduce(
                                  (sum, e) => sum + e.average,
                                  0,
                              ) /
                                  semesters.length) *
                                  10,
                          ) / 10,
                  },
              ]
            : [];
    });

const progress = computed(() =>
    progressView.value === 'semester'
        ? {
              periods: SEMESTER_PERIODS,
              caption: 'Moyenne générale par semestre',
              description: 'Moyenne générale de chaque semestre du cursus',
              points: SEMESTER_AVERAGES.map(({ semester, average }) => ({
                  position: semester,
                  average,
                  title: `Semestre ${semester}`,
              })),
          }
        : {
              periods: YEAR_PERIODS,
              caption: "Moyenne générale par année d'apprentissage",
              description: "Moyenne générale de chaque année d'apprentissage",
              points: yearPoints(),
          },
);
</script>

<template>
    <section
        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        aria-label="Indicateurs"
    >
        <StatTile
            label="Moyenne générale"
            :value="FINAL_GRADE.grade.toFixed(1)"
            hint="Note finale CFC estimée"
            :icon="GraduationCapIcon"
        />
        <StatTile
            label="Période en cours"
            :value="`Année ${period.year}`"
            :hint="`Semestre ${period.semesterInYear} · S${period.semester} du cursus`"
            :icon="CalendarIcon"
        />
        <StatTile
            label="Notes ce semestre"
            :value="String(GRADES_THIS_SEMESTER)"
            :hint="`Saisies depuis le début du S${period.semester}`"
            :icon="FileTextIcon"
        />
        <StatTile
            label="Tendance"
            :value="
                trend
                    ? `${trend.delta >= 0 ? '+' : ''}${trend.delta.toFixed(1)}`
                    : '—'
            "
            :icon="trend && trend.delta < 0 ? TrendingDownIcon : TrendingUpIcon"
        >
            <template v-if="trend" #hint>
                Par rapport au semestre {{ trend.since }}
            </template>
        </StatTile>
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
        <Card class="lg:col-span-2">
            <CardHeader>
                <CardTitle>Progression</CardTitle>
                <CardDescription>{{ progress.description }}</CardDescription>
                <CardAction>
                    <TabFilter
                        v-model="progressView"
                        :options="PROGRESS_VIEWS"
                        label="Afficher la progression par"
                    />
                </CardAction>
            </CardHeader>
            <CardContent>
                <ProgressChart
                    :data="progress.points"
                    :periods="progress.periods"
                    :caption="progress.caption"
                />
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Domaines</CardTitle>
                <CardDescription>Moyenne et poids dans le CFC</CardDescription>
            </CardHeader>
            <CardContent>
                <DomainProgressList :domains="DOMAIN_GRADES" />
            </CardContent>
        </Card>
    </section>

    <Card>
        <CardHeader>
            <CardTitle>Dernières notes</CardTitle>
            <CardDescription>Vos notes les plus récentes</CardDescription>
            <CardAction>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="grades.dashboard()">
                        Voir le carnet
                        <ChevronRightIcon aria-hidden="true" />
                    </Link>
                </Button>
            </CardAction>
        </CardHeader>
        <CardContent>
            <ul class="divide-y">
                <li v-for="grade in latestGrades" :key="grade.id">
                    <Link
                        :href="grades.show(grade.id)"
                        class="hover:bg-muted/50 focus-visible:ring-ring/50 -mx-2 flex items-center gap-4 rounded-md px-2 py-3 transition-colors focus-visible:ring-[3px] focus-visible:outline-none"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ grade.title }}
                            </p>
                            <p class="text-muted-foreground text-xs">
                                {{ grade.subject }} · {{ grade.date }}
                            </p>
                        </div>
                        <span class="text-lg font-semibold tabular-nums">
                            {{ grade.value.toFixed(1) }}
                        </span>
                        <ChevronRightIcon
                            class="text-muted-foreground size-4"
                            aria-hidden="true"
                        />
                    </Link>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
