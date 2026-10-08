<script setup lang="ts">
import { Head, Link, usePage, useRemember } from '@inertiajs/vue3';
import { BookOpenIcon, ChartLineIcon, FolderOpenIcon } from '@lucide/vue';
import { computed, reactive } from 'vue';
import ApprenticeAvatar from '@/components/apprentice/ApprenticeAvatar.vue';
import ApprenticeOverview from '@/components/apprentice/ApprenticeOverview.vue';
import ApprenticePortfolio from '@/components/apprentice/ApprenticePortfolio.vue';
import AssignmentBadge from '@/components/apprentice/AssignmentBadge.vue';
import StatusBadge from '@/components/apprentice/StatusBadge.vue';
import TrackBadges from '@/components/apprentice/TrackBadges.vue';
import GradebookSummary from '@/components/gradebook/GradebookSummary.vue';
import GradeList from '@/components/gradebook/GradeList.vue';
import { PageContainer } from '@/components/page';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    averageStatus,
    relativeDate,
    situationOf,
    yearLabel,
} from '@/lib/apprentice';
import { apprentisdashboard } from '@/routes';
import apprentices from '@/routes/apprentices';
import type { Apprentice, ApprenticeStats } from '@/types/apprentice';
import { createGradebook, type GradeTree } from '@/lib/gradebook';
import type { Grade } from '@/types/grade';
import type { SupervisedPortfolio } from '@/types/portfolio';

const props = defineProps<{
    apprenticeId: number;
    apprentice: Apprentice;
    /** Mêmes statistiques que la ligne de la liste (`ApprenticeList::statsFor`). */
    stats: ApprenticeStats;
    grades: Grade[];
    /** Null quand l'utilisateur n'a pas le droit de voir le portfolio. */
    portfolio: SupervisedPortfolio | null;
    /** Arbre de notes de l'apprenti·e ; null sans filière attribuée. */
    tree: GradeTree | null;
}>();

/** Moyennes pondérées du CFC, comme sur le carnet de l'apprenti·e. */
const gradebook = computed(() =>
    props.tree ? createGradebook(props.tree, props.grades) : null,
);

const gradeHref = (grade: Grade) =>
    apprentices.grades.show({
        apprentice: props.apprenticeId,
        grade: grade.id,
    });

const situation = computed(() => situationOf({ stats: props.stats }));

const averageClass = computed(() =>
    props.stats.average === null
        ? 'text-muted-foreground'
        : averageStatus(props.stats.average).class,
);

/** Onglets soulignés plutôt qu'en pastilles. */
const TAB_TRIGGER_CLASS =
    'text-muted-foreground hover:text-foreground data-[state=active]:text-foreground data-[state=active]:border-primary dark:data-[state=active]:border-primary h-auto flex-none rounded-none border-0 border-b-2 border-transparent px-3 pt-2 pb-3 data-[state=active]:bg-transparent data-[state=active]:shadow-none dark:data-[state=active]:bg-transparent';

type ProfileTab = 'overview' | 'grades' | 'portfolio';

/** `?tab=grades` ou `?tab=portfolio` ouvre directement l'onglet (liens de la liste). */
const page = usePage();
const queryTab = new URLSearchParams(page.url.split('?')[1] ?? '').get('tab');

function toTab(value: unknown): ProfileTab {
    if (value === 'grades') {
        return 'grades';
    }

    return value === 'portfolio' && props.portfolio ? 'portfolio' : 'overview';
}

// Avec un objet reactive, useRemember renvoie ce même objet (pas un Ref).
const view = useRemember(
    reactive({ tab: toTab(queryTab) }),
    'ApprenticeShow',
) as { tab: ProfileTab };

function selectTab(tab: string | number): void {
    view.tab = toTab(tab);
}
</script>

<template>
    <Head :title="apprentice.name" />

    <PageContainer size="lg">
        <Breadcrumb>
            <BreadcrumbList>
                <BreadcrumbItem>
                    <BreadcrumbLink as-child>
                        <Link :href="apprentisdashboard()">Apprentis</Link>
                    </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />
                <BreadcrumbItem>
                    <BreadcrumbPage>{{ apprentice.name }}</BreadcrumbPage>
                </BreadcrumbItem>
            </BreadcrumbList>
        </Breadcrumb>

        <Card class="gap-0 overflow-hidden py-0 shadow-xs">
            <!-- Bandeau décoratif : dégradé de la marque et trame de points. -->
            <div
                class="from-primary/25 via-primary/10 to-chart-2/25 relative h-28 bg-linear-to-r sm:h-32"
                aria-hidden="true"
            >
                <div
                    class="absolute inset-0 bg-[radial-gradient(var(--color-foreground)_1px,transparent_1px)] [background-size:16px_16px] opacity-[0.07]"
                />
            </div>

            <div class="flex flex-col gap-5 px-6 pb-6">
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div
                        class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-end"
                    >
                        <ApprenticeAvatar
                            :name="apprentice.name"
                            class="ring-card -mt-14 size-24 shadow-sm ring-4"
                            fallback-class="text-2xl"
                        />
                        <div class="flex min-w-0 flex-col gap-1.5 pt-2 sm:pb-1">
                            <h1
                                class="truncate text-2xl font-semibold tracking-tight"
                            >
                                {{ apprentice.name }}
                            </h1>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <StatusBadge
                                    :tone="situation.tone"
                                    :label="situation.label"
                                />
                                <TrackBadges
                                    :track="apprentice.track"
                                    :is-mp="apprentice.isMp"
                                />
                                <span
                                    v-if="apprentice.year"
                                    class="text-muted-foreground text-sm"
                                    title="Déduite du dernier semestre noté"
                                >
                                    · {{ yearLabel(apprentice.year) }}
                                </span>
                                <Badge
                                    v-if="!apprentice.isActive"
                                    variant="outline"
                                >
                                    Inactif
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chiffres clés et suivi, séparés par des filets. -->
                <dl
                    class="bg-border grid grid-cols-2 gap-px overflow-hidden rounded-xl border lg:grid-cols-5"
                >
                    <div class="bg-card flex flex-col gap-1 p-4">
                        <dt class="text-muted-foreground text-xs font-medium">
                            Moyenne
                        </dt>
                        <dd
                            class="text-xl font-semibold tabular-nums"
                            :class="averageClass"
                        >
                            {{
                                stats.average !== null
                                    ? stats.average.toFixed(1)
                                    : '—'
                            }}
                        </dd>
                    </div>
                    <div class="bg-card flex flex-col gap-1 p-4">
                        <dt class="text-muted-foreground text-xs font-medium">
                            Notes saisies
                        </dt>
                        <dd class="text-xl font-semibold tabular-nums">
                            {{ stats.grades_count }}
                        </dd>
                    </div>
                    <div class="bg-card flex flex-col gap-1 p-4">
                        <dt class="text-muted-foreground text-xs font-medium">
                            Dernière note
                        </dt>
                        <dd
                            class="text-xl font-semibold first-letter:uppercase"
                            :title="stats.last_grade_date ?? undefined"
                        >
                            {{
                                stats.last_grade_date
                                    ? relativeDate(stats.last_grade_date)
                                    : '—'
                            }}
                        </dd>
                    </div>
                    <div class="bg-card flex flex-col gap-1.5 p-4">
                        <dt class="text-muted-foreground text-xs font-medium">
                            Coach
                        </dt>
                        <dd class="text-sm">
                            <AssignmentBadge
                                :value="apprentice.coach ?? undefined"
                            />
                        </dd>
                    </div>
                    <div
                        class="bg-card col-span-2 flex flex-col gap-1.5 p-4 lg:col-span-1"
                    >
                        <dt class="text-muted-foreground text-xs font-medium">
                            Formateur
                        </dt>
                        <dd class="text-sm">
                            <AssignmentBadge
                                :value="apprentice.trainer ?? undefined"
                            />
                        </dd>
                    </div>
                </dl>
            </div>
        </Card>

        <Tabs
            :model-value="view.tab"
            class="gap-6"
            @update:model-value="selectTab"
        >
            <TabsList
                class="h-auto w-full justify-start gap-2 overflow-x-auto rounded-none border-b bg-transparent p-0"
            >
                <TabsTrigger
                    value="overview"
                    :class="TAB_TRIGGER_CLASS"
                    data-test="profile-tab-overview"
                >
                    <ChartLineIcon aria-hidden="true" />
                    Vue d'ensemble
                </TabsTrigger>
                <TabsTrigger
                    value="grades"
                    :class="TAB_TRIGGER_CLASS"
                    data-test="profile-tab-grades"
                >
                    <BookOpenIcon aria-hidden="true" />
                    Carnet de notes
                    <Badge
                        variant="secondary"
                        class="h-5 min-w-5 rounded-full px-1.5 tabular-nums"
                    >
                        {{ grades.length }}
                    </Badge>
                </TabsTrigger>
                <TabsTrigger
                    v-if="portfolio"
                    value="portfolio"
                    :class="TAB_TRIGGER_CLASS"
                    data-test="profile-tab-portfolio"
                >
                    <FolderOpenIcon aria-hidden="true" />
                    Portfolio
                    <Badge
                        variant="secondary"
                        class="h-5 min-w-5 rounded-full px-1.5 tabular-nums"
                    >
                        {{ portfolio.projects.length }}
                    </Badge>
                </TabsTrigger>
            </TabsList>

            <TabsContent value="overview" class="flex flex-col gap-6">
                <ApprenticeOverview
                    v-if="gradebook"
                    :apprentice="apprentice"
                    :gradebook="gradebook"
                    :grades="grades"
                    :grade-href="gradeHref"
                />
                <p v-else class="text-muted-foreground text-sm">
                    Aucune filière n'est encore attribuée à cet·te apprenti·e :
                    ses indicateurs apparaîtront dès qu'elle le sera.
                </p>
            </TabsContent>

            <TabsContent value="grades" class="flex flex-col gap-6">
                <template v-if="gradebook">
                    <GradebookSummary :gradebook="gradebook" />
                    <GradeList
                        :gradebook="gradebook"
                        :grades="grades"
                        :grade-href="gradeHref"
                    />
                </template>
                <p v-else class="text-muted-foreground text-sm">
                    Aucune filière n'est encore attribuée à cet·te apprenti·e :
                    son carnet apparaîtra dès qu'elle le sera.
                </p>
            </TabsContent>

            <TabsContent
                v-if="portfolio"
                value="portfolio"
                class="flex flex-col gap-4"
            >
                <ApprenticePortfolio :portfolio="portfolio" />
            </TabsContent>
        </Tabs>
    </PageContainer>
</template>
