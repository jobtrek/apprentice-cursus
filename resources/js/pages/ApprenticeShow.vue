<script setup lang="ts">
import { Head, Link, usePage, useRemember } from '@inertiajs/vue3';
import { BookOpenIcon, FolderOpenIcon } from '@lucide/vue';
import { computed, reactive } from 'vue';
import ApprenticeAvatar from '@/components/apprentice/ApprenticeAvatar.vue';
import ApprenticeOverview from '@/components/apprentice/ApprenticeOverview.vue';
import ApprenticePortfolio from '@/components/apprentice/ApprenticePortfolio.vue';
import AssignmentBadge from '@/components/apprentice/AssignmentBadge.vue';
import StatusBadge from '@/components/apprentice/StatusBadge.vue';
import TrackBadges from '@/components/apprentice/TrackBadges.vue';
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
import { useNavigation } from '@/composables/useNavigation';
import {
    averageStatus,
    relativeDate,
    situationOf,
    yearLabel,
} from '@/lib/apprentice';
import { apprentisdashboard } from '@/routes';
import apprentices from '@/routes/apprentices';
import type { Apprentice, ApprenticeStats } from '@/types/apprentice';
import { createGradebook, fmt, type GradeTree } from '@/lib/gradebook';
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

/** Note finale CFC (racine de l'arbre). */
const finalAverage = computed(() => {
    const value = gradebook.value?.nodeValue(gradebook.value.root) ?? null;

    return value === null
        ? null
        : {
              value,
              status: averageStatus(value),
          };
});

const gradeHref = (grade: Grade) =>
    apprentices.grades.show({
        apprentice: props.apprenticeId,
        grade: grade.id,
    });

const situation = computed(() => situationOf({ stats: props.stats }));

/** Le coach ne lit pas son propre nom en « Coach », ni le formateur en « Formateur ». */
const { role } = useNavigation();

/** Onglets soulignés plutôt qu'en pastilles. */
const TAB_TRIGGER_CLASS =
    'text-muted-foreground hover:text-foreground data-[state=active]:text-foreground data-[state=active]:border-primary dark:data-[state=active]:border-primary h-auto flex-none rounded-none border-0 border-b-2 border-transparent px-3 pt-2 pb-3 data-[state=active]:bg-transparent data-[state=active]:shadow-none dark:data-[state=active]:bg-transparent';

type ProfileTab = 'grades' | 'portfolio';

/** `?tab=portfolio` ouvre directement le portfolio ; sinon les notes. */
const page = usePage();
const queryTab = new URLSearchParams(page.url.split('?')[1] ?? '').get('tab');

function toTab(value: unknown): ProfileTab {
    return value === 'portfolio' && props.portfolio ? 'portfolio' : 'grades';
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

        <!-- En-tête compact : identité à gauche, suivi à droite. -->
        <Card
            class="flex-col gap-4 p-5 shadow-xs lg:flex-row lg:items-center lg:justify-between"
        >
            <div class="flex min-w-0 items-center gap-4">
                <ApprenticeAvatar
                    :name="apprentice.name"
                    :tone="situation.tone"
                    class="size-14"
                    fallback-class="text-lg"
                />
                <div class="flex min-w-0 flex-col gap-1.5">
                    <h1 class="truncate text-2xl font-semibold tracking-tight">
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
                            v-if="finalAverage"
                            variant="outline"
                            class="tabular-nums"
                            title="Note finale CFC"
                            data-test="final-average-badge"
                        >
                            Note finale
                            <span
                                class="font-semibold"
                                :class="finalAverage.status.class"
                            >
                                {{ fmt(finalAverage.value) }}
                            </span>
                        </Badge>
                        <Badge v-if="!apprentice.isActive" variant="outline">
                            Inactif
                        </Badge>
                    </div>
                </div>
            </div>

            <dl
                class="flex flex-wrap gap-x-8 gap-y-3 border-t pt-4 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-8"
            >
                <div class="flex flex-col gap-1">
                    <dt class="text-muted-foreground text-xs font-medium">
                        Dernière note
                    </dt>
                    <dd
                        class="text-sm font-medium first-letter:uppercase"
                        :title="stats.last_grade_date ?? undefined"
                    >
                        {{
                            stats.last_grade_date
                                ? relativeDate(stats.last_grade_date)
                                : '—'
                        }}
                    </dd>
                </div>
                <div v-if="role !== 'coach'" class="flex flex-col gap-1">
                    <dt class="text-muted-foreground text-xs font-medium">
                        Coach
                    </dt>
                    <dd class="text-sm">
                        <AssignmentBadge
                            :value="apprentice.coach ?? undefined"
                        />
                    </dd>
                </div>
                <div v-if="role !== 'trainer'" class="flex flex-col gap-1">
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
                    value="grades"
                    :class="TAB_TRIGGER_CLASS"
                    data-test="profile-tab-grades"
                >
                    <BookOpenIcon aria-hidden="true" />
                    Notes
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

            <!-- Parcours et domaines, puis la liste filtrable des notes. -->
            <TabsContent value="grades" class="flex flex-col gap-6">
                <template v-if="gradebook">
                    <ApprenticeOverview
                        :apprentice="apprentice"
                        :gradebook="gradebook"
                    />
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
