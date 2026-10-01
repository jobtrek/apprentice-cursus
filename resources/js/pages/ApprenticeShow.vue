<script setup lang="ts">
import { Head, Link, usePage, useRemember } from '@inertiajs/vue3';
import {
    BookOpenIcon,
    ChartLineIcon,
    FolderOpenIcon,
    UserXIcon,
} from '@lucide/vue';
import { computed, reactive } from 'vue';
import ApprenticeMetaRow from '@/components/apprentice/ApprenticeMetaRow.vue';
import ApprenticeOverview from '@/components/apprentice/ApprenticeOverview.vue';
import ApprenticePortfolio from '@/components/apprentice/ApprenticePortfolio.vue';
import GradeBook from '@/components/gradeList/GradeBook.vue';
import { PageContainer, PageHeader } from '@/components/page';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { findApprentice } from '@/composables/useApprentices';
import { getInitials } from '@/composables/useInitials';
import { apprentisdashboard } from '@/routes';
import apprentices from '@/routes/apprentices';
import type { Grade } from '@/types/grade';
import type { SupervisedPortfolio } from '@/types/portfolio';

const props = defineProps<{
    apprenticeId: number;
    grades: Grade[];
    /** Null quand l'utilisateur n'a pas le droit de voir le portfolio. */
    portfolio: SupervisedPortfolio | null;
}>();

const apprentice = computed(() => findApprentice(props.apprenticeId));

const breadcrumbs = [{ label: 'Apprentis', href: apprentisdashboard() }];

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
    <Head :title="apprentice?.name ?? 'Apprenti·e introuvable'" />

    <PageContainer size="lg">
        <template v-if="apprentice">
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
                            <AvatarImage :src="apprentice.avatarUrl ?? ''" />
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
                                <Badge>{{ apprentice.track }}</Badge>
                                <Badge variant="secondary">
                                    {{ apprentice.year }} année
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <ApprenticeMetaRow
                        class="sm:w-80 sm:shrink-0"
                        :coach="apprentice.coach"
                        :formateur="apprentice.trainer"
                    />
                </CardContent>
            </Card>

            <Tabs
                :model-value="view.tab"
                class="gap-6"
                @update:model-value="selectTab"
            >
                <TabsList>
                    <TabsTrigger
                        value="overview"
                        data-test="profile-tab-overview"
                    >
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
                        :grade-href="gradeHref"
                    />
                </TabsContent>

                <TabsContent value="grades" class="flex flex-col gap-6">
                    <GradeBook :grades="grades" :grade-href="gradeHref" />
                </TabsContent>

                <TabsContent v-if="portfolio" value="portfolio">
                    <ApprenticePortfolio :portfolio="portfolio" />
                </TabsContent>
            </Tabs>
        </template>

        <template v-else>
            <PageHeader
                title="Apprenti·e introuvable"
                :breadcrumbs="breadcrumbs"
            />

            <Empty class="border">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <UserXIcon />
                    </EmptyMedia>
                    <EmptyTitle>Aucun·e apprenti·e ne correspond</EmptyTitle>
                    <EmptyDescription>
                        Cet·te apprenti·e n'existe pas ou n'est plus suivi·e.
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button as-child variant="outline">
                        <Link :href="apprentisdashboard()">
                            Retour à la liste
                        </Link>
                    </Button>
                </EmptyContent>
            </Empty>
        </template>
    </PageContainer>
</template>
