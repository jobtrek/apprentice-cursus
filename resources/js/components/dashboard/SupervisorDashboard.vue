<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChevronRightIcon,
    TrendingDownIcon,
    TrendingUpIcon,
    TriangleAlertIcon,
    UserRoundXIcon,
    UsersIcon,
    SigmaIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import AverageByYearChart from '@/components/dashboard/AverageByYearChart.vue';
import StatTile from '@/components/dashboard/StatTile.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useApprentices } from '@/composables/useApprentices';
import {
    APPRENTICE_AVERAGES,
    PASSING_GRADE,
    type YearAverage,
} from '@/data/dashboard';
import { apprentisdashboard } from '@/routes';
import apprenticesRoutes from '@/routes/apprentices';

const YEARS = ['1ère', '2ème', '3ème', '4ème'] as const;

const { apprentices } = useApprentices();

/** Apprentis suivis, chacun avec sa moyenne et sa tendance. */
const rows = computed(() =>
    apprentices.value.flatMap((apprentice) => {
        const scores = APPRENTICE_AVERAGES.find(
            (entry) => entry.apprenticeId === apprentice.id,
        );

        return scores
            ? [
                  {
                      ...apprentice,
                      average: scores.average,
                      delta: scores.average - scores.previousAverage,
                  },
              ]
            : [];
    }),
);

const mean = (values: number[]) =>
    values.reduce((sum, value) => sum + value, 0) / (values.length || 1);

const groupAverage = computed(() => mean(rows.value.map((r) => r.average)));

const atRisk = computed(() =>
    rows.value
        .filter((row) => row.average < PASSING_GRADE)
        .sort((a, b) => a.average - b.average),
);

const withoutCoach = computed(
    () => apprentices.value.filter((apprentice) => !apprentice.coach).length,
);

const byYear = computed<YearAverage[]>(() =>
    YEARS.flatMap((label, index) => {
        const inYear = rows.value.filter((row) => row.year === label);

        return inYear.length
            ? [
                  {
                      year: index + 1,
                      label,
                      average: mean(inYear.map((row) => row.average)),
                      count: inYear.length,
                  },
              ]
            : [];
    }),
);
</script>

<template>
    <section
        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        aria-label="Indicateurs"
    >
        <StatTile
            label="Apprentis suivis"
            :value="String(rows.length)"
            hint="IT et EC confondus"
            :icon="UsersIcon"
        />
        <StatTile
            label="Moyenne du groupe"
            :value="groupAverage.toFixed(1)"
            hint="Moyenne générale actuelle"
            :icon="SigmaIcon"
        />
        <StatTile
            label="À suivre"
            :value="String(atRisk.length)"
            :hint="`Moyenne sous ${PASSING_GRADE.toFixed(1)}`"
            :icon="TriangleAlertIcon"
        />
        <StatTile
            label="Sans coach"
            :value="String(withoutCoach)"
            hint="Aucun coach assigné"
            :icon="UserRoundXIcon"
        />
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
        <Card class="lg:col-span-2">
            <CardHeader>
                <CardTitle>Moyenne par année d'apprentissage</CardTitle>
                <CardDescription>
                    Moyenne générale des apprentis, de la 1re à la 4e année. La
                    ligne pointillée marque le seuil de
                    {{ PASSING_GRADE.toFixed(1) }}.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <AverageByYearChart :data="byYear" />
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Apprentis à suivre</CardTitle>
                <CardDescription>
                    Moyenne sous {{ PASSING_GRADE.toFixed(1) }}
                </CardDescription>
                <CardAction>
                    <Button as-child variant="ghost" size="sm">
                        <Link :href="apprentisdashboard()">
                            Tous
                            <ChevronRightIcon aria-hidden="true" />
                        </Link>
                    </Button>
                </CardAction>
            </CardHeader>
            <CardContent>
                <ul v-if="atRisk.length" class="divide-y">
                    <li v-for="row in atRisk" :key="row.id">
                        <Link
                            :href="apprenticesRoutes.show(row.id)"
                            class="hover:bg-muted/50 focus-visible:ring-ring/50 -mx-2 flex items-center gap-3 rounded-md px-2 py-3 transition-colors focus-visible:ring-[3px] focus-visible:outline-none"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ row.name }}
                                </p>
                                <p
                                    class="text-muted-foreground flex items-center gap-1 text-xs"
                                >
                                    <component
                                        :is="
                                            row.delta < 0
                                                ? TrendingDownIcon
                                                : TrendingUpIcon
                                        "
                                        class="size-3.5"
                                        aria-hidden="true"
                                    />
                                    {{ row.delta >= 0 ? '+' : ''
                                    }}{{ row.delta.toFixed(1) }} ·
                                    {{ row.track }} · {{ row.year }} année
                                </p>
                            </div>
                            <span class="text-lg font-semibold tabular-nums">
                                {{ row.average.toFixed(1) }}
                            </span>
                        </Link>
                    </li>
                </ul>
                <p v-else class="text-muted-foreground text-sm">
                    Aucun apprenti sous le seuil pour le moment.
                </p>
            </CardContent>
        </Card>
    </section>
</template>
