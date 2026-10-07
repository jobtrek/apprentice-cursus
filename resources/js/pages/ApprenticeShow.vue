<script setup lang="ts">
import { Head, Link, usePage, useRemember } from '@inertiajs/vue3';
import { BookOpenIcon, ChartLineIcon, FolderOpenIcon } from '@lucide/vue';
import { computed, reactive } from 'vue';
import ApprenticeMetaRow from '@/components/apprentice/ApprenticeMetaRow.vue';
import ApprenticeOverview from '@/components/apprentice/ApprenticeOverview.vue';
import ApprenticePortfolio from '@/components/apprentice/ApprenticePortfolio.vue';
import GradebookSummary from '@/components/gradebook/GradebookSummary.vue';
import GradeList from '@/components/gradebook/GradeList.vue';
import { PageContainer } from '@/components/page';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { getInitials } from '@/composables/useInitials';
import { createGradebook, type GradeTree } from '@/lib/gradebook';
import { apprentisdashboard } from '@/routes';
import apprentices from '@/routes/apprentices';
import type { Apprentice } from '@/types/apprentice';
import type { Grade } from '@/types/grade';
import type { SupervisedPortfolio } from '@/types/portfolio';

const props = defineProps<{
    apprenticeId: number;
    apprentice: Apprentice;
    grades: Grade[];
    /** Null quand l'utilisateur n'a pas le droit de voir le portfolio. */
    portfolio: SupervisedPortfolio | null;
    /** Arbre d'évaluation de l'apprenti·e ; null sans filière. */
    tree: GradeTree | null;
}>();

/** Même calcul que le carnet de l'apprenti·e : moyennes pondérées du CFC. */
const gradebook = computed(() =>
    props.tree ? createGradebook(props.tree, props.grades) : null,
);

const gradeHref = (grade: Grade) =>
    apprentices.grades.show({
        apprentice: props.apprenticeId,
        grade: grade.id,
    });

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

        <Card>
            <CardContent
                class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex min-w-0 items-center gap-4">
                    <Avatar class="size-16 text-lg">
                        <AvatarFallback>
                            {{ getInitials(apprentice.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                        <h1
                            class="truncate text-2xl font-semibold tracking-tight"
                        >
                            {{ apprentice.name }}
                        </h1>
                        <div class="flex flex-wrap gap-1.5">
                            <Badge v-if="apprentice.track">
                                {{ apprentice.track }}
                            </Badge>
                            <Badge
                                v-if="!apprentice.isActive"
                                variant="outline"
                            >
                                Inactif
                            </Badge>
                        </div>
                    </div>
                </div>

                <ApprenticeMetaRow
                    class="sm:w-80 sm:shrink-0"
                    :coach="apprentice.coach ?? undefined"
                    :formateur="apprentice.trainer ?? undefined"
                />
            </CardContent>
        </Card>

        <Tabs
            :model-value="view.tab"
            class="gap-6"
            @update:model-value="selectTab"
        >
            <!-- Pleine largeur sur mobile, icônes masquées : les trois onglets tiennent. -->
            <TabsList class="w-full sm:w-fit [&_svg]:max-sm:hidden">
                <TabsTrigger value="overview" data-test="profile-tab-overview">
                    <ChartLineIcon aria-hidden="true" />
                    Vue d'ensemble
                </TabsTrigger>
                <TabsTrigger value="grades" data-test="profile-tab-grades">
                    <BookOpenIcon aria-hidden="true" />
                    Carnet de notes
                </TabsTrigger>
                <TabsTrigger
                    v-if="portfolio"
                    value="portfolio"
                    data-test="profile-tab-portfolio"
                >
                    <FolderOpenIcon aria-hidden="true" />
                    Portfolio
                    <Badge variant="secondary" class="tabular-nums">
                        {{ portfolio.projects.length }}
                    </Badge>
                </TabsTrigger>
            </TabsList>

            <TabsContent value="overview" class="flex flex-col gap-6">
                <ApprenticeOverview
                    :grades="grades"
                    :gradebook="gradebook"
                    :grade-href="gradeHref"
                />
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
                    Aucune filière n'est encore attribuée à cet apprenti : le
                    carnet apparaîtra dès qu'elle le sera.
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
