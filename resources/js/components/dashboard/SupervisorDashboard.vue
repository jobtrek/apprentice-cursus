<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChevronRightIcon,
    ClockIcon,
    TriangleAlertIcon,
    UserPlusIcon,
    UsersIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import AverageValue from '@/components/apprentice/AverageValue.vue';
import ActivityChart from '@/components/dashboard/ActivityChart.vue';
import LatestGrades from '@/components/dashboard/LatestGrades.vue';
import SituationOverview from '@/components/dashboard/SituationOverview.vue';
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
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { PASSING_GRADE } from '@/lib/grade-scale';
import { hasNoRecentGrade, STALE_AFTER_DAYS } from '@/lib/apprentice';
import { formatDate } from '@/lib/date';
import { apprentisdashboard } from '@/routes';
import apprenticesRoutes from '@/routes/apprentices';
import type {
    ApprenticeListItem,
    MonthlyActivity,
    SupervisedGrade,
} from '@/types/apprentice';

/**
 * Accueil du coach ou du formateur : l'état du groupe, son activité, ce qui
 * est nouveau et ce qui demande une action. La liste complète est sur la page
 * Apprentis.
 */
const props = defineProps<{
    /** Apprentis suivis, comme sur la page Apprentis (`ApprenticeList::for()`). */
    apprentices: ApprenticeListItem[];
    /** Dernières notes des apprentis suivis (`ApprenticeList::latestGrades()`). */
    latestGrades: SupervisedGrade[];
    /** Notes par mois sur 12 mois (`ApprenticeList::monthlyActivity()`). */
    activity: MonthlyActivity[];
}>();

const graded = computed(() =>
    props.apprentices.filter(({ stats }) => stats.average !== null),
);

const averageOf = (apprentice: ApprenticeListItem): number =>
    apprentice.stats.average ?? 0;

const atRisk = computed(() =>
    graded.value
        .filter((row) => averageOf(row) < PASSING_GRADE)
        .sort((a, b) => averageOf(a) - averageOf(b)),
);

const stale = computed(() =>
    props.apprentices.filter(({ stats }) =>
        hasNoRecentGrade(stats.last_grade_date),
    ),
);

const describe = ({ track, stats }: ApprenticeListItem): string =>
    [
        track,
        `${stats.grades_count} note${stats.grades_count > 1 ? 's' : ''}`,
        stats.last_grade_date
            ? `dernière le ${formatDate(stats.last_grade_date)}`
            : null,
    ]
        .filter(Boolean)
        .join(' · ');
</script>

<template>
    <Empty v-if="apprentices.length === 0" class="border">
        <EmptyHeader>
            <EmptyMedia variant="icon">
                <UsersIcon />
            </EmptyMedia>
            <EmptyTitle>Vous ne suivez encore aucun apprenti</EmptyTitle>
            <EmptyDescription>
                Ajoutez depuis la liste les apprentis que vous suivez, pour
                retrouver ici leurs moyennes.
            </EmptyDescription>
        </EmptyHeader>
        <EmptyContent>
            <Button as-child>
                <Link :href="apprentisdashboard()">
                    <UserPlusIcon aria-hidden="true" />
                    Ajouter un apprenti
                </Link>
            </Button>
        </EmptyContent>
    </Empty>

    <template v-else>
        <section class="grid gap-4 lg:grid-cols-3" aria-label="Vue du groupe">
            <SituationOverview :apprentices="apprentices" />
            <ActivityChart :activity="activity" class="lg:col-span-2" />
        </section>

        <section class="grid items-start gap-4 lg:grid-cols-3">
            <LatestGrades :grades="latestGrades" class="lg:col-span-2" />

            <!-- À traiter : sous le seuil, puis carnets à relancer. -->
            <div class="flex flex-col gap-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TriangleAlertIcon
                                class="text-destructive size-4"
                                aria-hidden="true"
                            />
                            À suivre
                        </CardTitle>
                        <CardDescription>
                            Moyenne sous {{ PASSING_GRADE.toFixed(1) }}, la plus
                            basse en premier
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
                                            class="text-muted-foreground text-xs"
                                        >
                                            {{ describe(row) }}
                                        </p>
                                    </div>
                                    <span class="text-lg">
                                        <AverageValue
                                            :average="row.stats.average"
                                        />
                                    </span>
                                    <ChevronRightIcon
                                        class="text-muted-foreground size-4"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </li>
                        </ul>
                        <p v-else class="text-muted-foreground text-sm">
                            Aucun apprenti sous le seuil pour le moment.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ClockIcon
                                class="text-muted-foreground size-4"
                                aria-hidden="true"
                            />
                            Carnet à mettre à jour
                        </CardTitle>
                        <CardDescription>
                            Aucune note depuis {{ STALE_AFTER_DAYS }} jours, ou
                            aucune note saisie : pensez à leur rappeler.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ul v-if="stale.length" class="divide-y">
                            <li v-for="row in stale" :key="row.id">
                                <Link
                                    :href="apprenticesRoutes.show(row.id)"
                                    class="hover:bg-muted/50 focus-visible:ring-ring/50 -mx-2 flex items-center gap-3 rounded-md px-2 py-2.5 transition-colors focus-visible:ring-[3px] focus-visible:outline-none"
                                >
                                    <span
                                        class="min-w-0 flex-1 truncate text-sm"
                                    >
                                        {{ row.name }}
                                    </span>
                                    <span
                                        class="text-muted-foreground text-xs tabular-nums"
                                    >
                                        {{
                                            row.stats.last_grade_date
                                                ? formatDate(
                                                      row.stats.last_grade_date,
                                                  )
                                                : 'Aucune note'
                                        }}
                                    </span>
                                </Link>
                            </li>
                        </ul>
                        <p v-else class="text-muted-foreground text-sm">
                            Tous vos apprentis ont une note récente.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </section>
    </template>
</template>
