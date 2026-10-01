<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChevronRightIcon,
    ClockIcon,
    SigmaIcon,
    TriangleAlertIcon,
    UserPlusIcon,
    UsersIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import AverageValue from '@/components/apprentice/AverageValue.vue';
import ApprenticeAveragesChart, {
    type ApprenticeAverage,
} from '@/components/dashboard/ApprenticeAveragesChart.vue';
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
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { PASSING_GRADE } from '@/data/dashboard';
import { hasNoRecentGrade, STALE_AFTER_DAYS } from '@/lib/apprentice';
import { apprentisdashboard } from '@/routes';
import apprenticesRoutes from '@/routes/apprentices';
import type { ApprenticeListItem } from '@/types/apprentice';

const props = defineProps<{
    /** Apprentis suivis, comme sur la page Apprentis (`ApprenticeList::for()`). */
    apprentices: ApprenticeListItem[];
    /** Apprentis que l'utilisateur peut encore ajouter. */
    assignableCount: number;
}>();

const graded = computed(() =>
    props.apprentices.filter(({ stats }) => stats.average !== null),
);

const averageOf = (apprentice: ApprenticeListItem): number =>
    apprentice.stats.average ?? 0;

const groupAverage = computed(() =>
    graded.value.length > 0
        ? graded.value.reduce((sum, row) => sum + averageOf(row), 0) /
          graded.value.length
        : null,
);

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

const chartData = computed<ApprenticeAverage[]>(() =>
    graded.value.map((row, index) => ({
        position: index + 1,
        name: row.name,
        average: averageOf(row),
    })),
);

const describe = ({ track, stats }: ApprenticeListItem): string =>
    [
        track,
        `${stats.grades_count} note${stats.grades_count > 1 ? 's' : ''}`,
        stats.last_grade_date ? `dernière le ${stats.last_grade_date}` : null,
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
        <section
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
            aria-label="Indicateurs"
        >
            <StatTile
                label="Apprentis suivis"
                :value="String(apprentices.length)"
                :hint="`${graded.length} avec au moins une note`"
                :icon="UsersIcon"
            />
            <StatTile
                label="Moyenne du groupe"
                :value="groupAverage !== null ? groupAverage.toFixed(1) : '—'"
                hint="Moyenne simple des notes saisies"
                :icon="SigmaIcon"
            />
            <StatTile
                label="Sous la moyenne"
                :value="String(atRisk.length)"
                :hint="`Moyenne sous ${PASSING_GRADE.toFixed(1)}`"
                :icon="TriangleAlertIcon"
            />
            <StatTile
                label="À ajouter"
                :value="String(assignableCount)"
                hint="Apprentis disponibles dans la liste"
                :icon="UserPlusIcon"
            />
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Moyenne par apprenti</CardTitle>
                    <CardDescription>
                        Apprentis suivis ayant au moins une note. La ligne
                        pointillée marque le seuil de
                        {{ PASSING_GRADE.toFixed(1) }}.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ApprenticeAveragesChart
                        v-if="chartData.length > 0"
                        :data="chartData"
                    />
                    <p v-else class="text-muted-foreground text-sm">
                        Aucune note saisie pour l'instant.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>À suivre</CardTitle>
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
                                    <p class="text-muted-foreground text-xs">
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
        </section>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <ClockIcon
                        class="text-muted-foreground size-4"
                        aria-hidden="true"
                    />
                    Sans note récente
                </CardTitle>
                <CardDescription>
                    Aucune note saisie depuis {{ STALE_AFTER_DAYS }} jours :
                    pensez à leur rappeler de mettre leur carnet à jour.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <ul
                    v-if="stale.length"
                    class="grid gap-x-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <li v-for="row in stale" :key="row.id">
                        <Link
                            :href="apprenticesRoutes.show(row.id)"
                            class="hover:bg-muted/50 focus-visible:ring-ring/50 -mx-2 flex items-center gap-3 rounded-md px-2 py-2.5 transition-colors focus-visible:ring-[3px] focus-visible:outline-none"
                        >
                            <span class="min-w-0 flex-1 truncate text-sm">
                                {{ row.name }}
                            </span>
                            <span
                                class="text-muted-foreground text-xs tabular-nums"
                            >
                                {{ row.stats.last_grade_date ?? 'Aucune note' }}
                            </span>
                        </Link>
                    </li>
                </ul>
                <p v-else class="text-muted-foreground text-sm">
                    Tous vos apprentis ont une note récente.
                </p>
            </CardContent>
        </Card>
    </template>
</template>
