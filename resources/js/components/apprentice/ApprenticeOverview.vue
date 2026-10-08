<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChevronRightIcon,
    CircleCheckIcon,
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
import type { DomainGrade } from '@/data/gradebook';
import { fmtWeight, type Gradebook } from '@/lib/gradebook';
import type { Grade } from '@/types/grade';
import type { RouteDefinition } from '@/wayfinder';

const props = defineProps<{
    /** Notes de l'apprenti·e, de la plus ancienne à la plus récente. */
    grades: Grade[];
    /** Arbre de l'apprenti·e ; null sans filière (pas de domaines affichés). */
    gradebook: Gradebook | null;
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

/**
 * Note finale CFC provisoire, la même que dans le carnet ; à défaut d'arbre
 * (apprenti·e sans filière), la moyenne simple des notes.
 */
const final = computed(() =>
    props.gradebook
        ? props.gradebook.nodeValue(props.gradebook.root)
        : mean(props.grades),
);

/**
 * Note finale provisoire tant qu'une évaluation comptée n'a pas de note, même
 * si chaque domaine en a déjà une (la moyenne se calcule alors sans elle).
 */
const provisional = computed(
    () => props.gradebook !== null && !props.gradebook.isComplete(),
);

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

/**
 * Moyenne pondérée de chaque domaine noté et son poids dans le CFC, tirés de
 * l'arbre d'évaluation : les mêmes chiffres que dans le carnet de notes.
 */
const domains = computed<DomainGrade[]>(() => {
    const book = props.gradebook;

    if (!book) {
        return [];
    }

    return book.domains.flatMap((id) => {
        const value = book.nodeValue(id);

        return value === null
            ? []
            : [
                  {
                      title: book.name(id),
                      weight: `${fmtWeight(book.weightOf(book.root, id))} %`,
                      grade: value,
                  },
              ];
    });
});

const insufficient = computed(() =>
    props.grades.filter((grade) => grade.value < PASSING_GRADE),
);

const recentGrades = computed(() => props.grades.slice(-5).reverse());
</script>

<template>
    <section
        class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4"
        aria-label="Indicateurs"
    >
        <StatTile
            :label="gradebook ? 'Note finale CFC' : 'Moyenne des notes'"
            :value="final !== null ? final.toFixed(1) : '—'"
            :hint="
                final === null
                    ? 'Aucune note pour l’instant'
                    : `${final < PASSING_GRADE ? 'Insuffisante' : 'Suffisante'}${provisional ? ' · provisoire' : ''}`
            "
            :icon="GraduationCapIcon"
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
        />
        <StatTile
            label="Notes insuffisantes"
            :value="String(insufficient.length)"
            :hint="`Notes sous ${PASSING_GRADE.toFixed(1)}`"
            :icon="TriangleAlertIcon"
        />
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
        <Card class="lg:col-span-2">
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

        <Card>
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
        <Card>
            <CardHeader>
                <CardTitle>À surveiller</CardTitle>
                <CardDescription>
                    Notes sous {{ PASSING_GRADE.toFixed(1) }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <ul v-if="insufficient.length" class="divide-y">
                    <li v-for="grade in insufficient" :key="grade.id">
                        <Link
                            :href="gradeHref(grade)"
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
                            <span
                                class="text-destructive text-lg font-semibold tabular-nums"
                            >
                                {{ grade.value.toFixed(1) }}
                            </span>
                            <ChevronRightIcon
                                class="text-muted-foreground size-4"
                                aria-hidden="true"
                            />
                        </Link>
                    </li>
                </ul>
                <p
                    v-else-if="grades.length === 0"
                    class="text-muted-foreground rounded-lg border border-dashed p-4 text-sm"
                >
                    Aucune note pour l’instant.
                </p>
                <div
                    v-else
                    class="flex items-center gap-3 rounded-lg border border-dashed p-4"
                >
                    <CircleCheckIcon
                        class="text-success size-5 shrink-0"
                        aria-hidden="true"
                    />
                    <div class="flex flex-col">
                        <p class="text-sm font-medium">
                            Aucune note insuffisante
                        </p>
                        <p class="text-muted-foreground text-xs">
                            Toutes les notes sont au moins à
                            {{ PASSING_GRADE.toFixed(1) }}.
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Dernières notes</CardTitle>
                <CardDescription>Les 5 notes les plus récentes</CardDescription>
            </CardHeader>
            <CardContent>
                <ul v-if="recentGrades.length" class="divide-y">
                    <li v-for="grade in recentGrades" :key="grade.id">
                        <Link
                            :href="gradeHref(grade)"
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
                            <span
                                class="text-lg font-semibold tabular-nums"
                                :class="{
                                    'text-destructive':
                                        grade.value < PASSING_GRADE,
                                }"
                            >
                                {{ grade.value.toFixed(1) }}
                            </span>
                            <ChevronRightIcon
                                class="text-muted-foreground size-4"
                                aria-hidden="true"
                            />
                        </Link>
                    </li>
                </ul>
                <p v-else class="text-muted-foreground text-sm">
                    Aucune note pour l'instant.
                </p>
            </CardContent>
        </Card>
    </section>
</template>
