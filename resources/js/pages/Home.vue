<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BookOpenIcon,
    CirclePlusIcon,
    FolderKanbanIcon,
    UserPlusIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import SupervisorDashboard from '@/components/dashboard/SupervisorDashboard.vue';
import AddGradeDialog from '@/components/grade/AddGradeDialog.vue';
import ChartCard from '@/components/gradebook/ChartCard.vue';
import DistributionChart from '@/components/gradebook/DistributionChart.vue';
import DomainChart from '@/components/gradebook/DomainChart.vue';
import HatchPattern from '@/components/gradebook/HatchPattern.vue';
import RecentGrades from '@/components/gradebook/RecentGrades.vue';
import SemesterChart from '@/components/gradebook/SemesterChart.vue';
import ShortcutGrid, {
    type Shortcut,
} from '@/components/gradebook/ShortcutGrid.vue';
import StatTiles from '@/components/gradebook/StatTiles.vue';
import TrainingPath from '@/components/gradebook/TrainingPath.vue';
import { PageContainer, PageHeader } from '@/components/page';
import { Button } from '@/components/ui/button';
import { useAddGradeDialog } from '@/composables/useAddGradeDialog';
import { useNavigation } from '@/composables/useNavigation';
import {
    createGradebook,
    type GradeTree,
    lastGradedSemester,
} from '@/lib/gradebook';
import { apprentisdashboard } from '@/routes';
import gradeRoutes from '@/routes/grades';
import portfolio from '@/routes/portfolio';
import type {
    ApprenticeListItem,
    MonthlyActivity,
    SupervisedGrade,
} from '@/types/apprentice';
import type { Grade } from '@/types/grade';

const props = defineProps<{
    grades: Grade[];
    tree: GradeTree | null;
    profile: { track: string | null; variant: 'standard' | 'mp' } | null;
    /** Coach, formateur, admin : lignes de la liste des apprentis. */
    apprentices: ApprenticeListItem[];
    /** Apprentis qu'un coach ou formateur peut encore ajouter. */
    assignableCount: number;
    /** Coach, formateur, admin : dernières notes des apprentis suivis. */
    latestGrades: SupervisedGrade[];
    /** Coach, formateur, admin : notes par mois sur 12 mois. */
    activity: MonthlyActivity[];
}>();

const page = usePage();
const { role } = useNavigation();
const { open: openAddGrade } = useAddGradeDialog();

const firstName = computed(
    () => page.props.auth?.user?.name?.split(/\s+/)[0] ?? '',
);

/** Coachs et formateurs suivent des apprentis ; les autres rôles voient leur propre parcours. */
const isApprentice = computed(() => role.value === 'apprentice');

/** Seuls coachs et formateurs s'ajoutent des apprentis, pas l'admin. */
const canAssign = computed(
    () => role.value === 'coach' || role.value === 'trainer',
);

const gradebook = computed(() =>
    props.tree ? createGradebook(props.tree, props.grades) : null,
);

const currentSemester = computed(() => lastGradedSemester(props.grades));

const shortcuts: Shortcut[] = [
    {
        href: gradeRoutes.dashboard(),
        icon: BookOpenIcon,
        title: 'Carnet de notes',
        description: 'Consultez les notes et les moyennes par domaine.',
    },
    {
        icon: CirclePlusIcon,
        title: 'Ajouter une note',
        description: 'Saisissez une nouvelle note et déposez le justificatif.',
    },
    {
        href: portfolio.index(),
        icon: FolderKanbanIcon,
        title: 'Portfolio',
        description: 'Gérez vos projets et exportez votre portfolio.',
    },
];
</script>

<template>
    <Head title="Accueil" />

    <PageContainer size="lg">
        <template v-if="isApprentice">
            <PageHeader
                :title="firstName ? `Bonjour ${firstName}` : 'Accueil'"
                description="Voici où vous en êtes dans votre formation."
            />

            <template v-if="gradebook">
                <TrainingPath
                    :gradebook="gradebook"
                    :current-semester="currentSemester"
                    :profile="profile"
                    derived
                />

                <StatTiles :gradebook="gradebook" :grades="grades" />

                <div class="chart-grid">
                    <SemesterChart
                        :gradebook="gradebook"
                        :current-semester="currentSemester"
                    />
                    <DomainChart :gradebook="gradebook">
                        <template #link>
                            <Link
                                :href="gradeRoutes.dashboard()"
                                class="chart-card__link"
                            >
                                Voir le carnet de notes →
                            </Link>
                        </template>
                    </DomainChart>
                </div>

                <div class="chart-grid">
                    <DistributionChart :grades="grades" />
                    <ChartCard
                        label-id="t-recent"
                        title="Dernières notes"
                        description="Les 5 plus récentes"
                    >
                        <RecentGrades :grades="grades" />
                    </ChartCard>
                </div>
            </template>

            <h2 class="section-title">Accès rapide</h2>
            <ShortcutGrid :items="shortcuts" @select="openAddGrade" />

            <HatchPattern />
            <AddGradeDialog />
        </template>

        <template v-else>
            <PageHeader
                :title="firstName ? `Bonjour ${firstName}` : 'Accueil'"
                description="Voici où en sont les apprentis que vous suivez."
            >
                <!-- L'ajout se fait depuis la liste, qui a la fenêtre de choix. -->
                <template v-if="canAssign && apprentices.length > 0" #actions>
                    <Button as-child variant="outline">
                        <Link :href="apprentisdashboard()">
                            <UserPlusIcon aria-hidden="true" />
                            Ajouter un apprenti
                            <span
                                v-if="assignableCount > 0"
                                class="text-muted-foreground tabular-nums"
                            >
                                ({{ assignableCount }})
                            </span>
                        </Link>
                    </Button>
                </template>
            </PageHeader>
            <SupervisorDashboard
                :apprentices="apprentices"
                :latest-grades="latestGrades"
                :activity="activity"
            />
        </template>
    </PageContainer>
</template>
