<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { BookOpenIcon, CirclePlusIcon, FolderKanbanIcon } from '@lucide/vue';
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
import { PageContainer, PageHeader } from '@/components/page';
import { useAddGradeDialog } from '@/composables/useAddGradeDialog';
import { useNavigation } from '@/composables/useNavigation';
import { DEMO_APPRENTICESHIP_START_YEAR } from '@/data/dashboard';
import { createGradebook, SEMESTERS, type GradeTree } from '@/lib/gradebook';
import { trainingPeriod } from '@/lib/semester';
import gradeRoutes from '@/routes/grades';
import portfolio from '@/routes/portfolio';
import type { ApprenticeListItem } from '@/types/apprentice';
import type { Grade } from '@/types/grade';

const props = defineProps<{
    grades: Grade[];
    tree: GradeTree | null;
    profile: { track: string | null; variant: 'standard' | 'mp' } | null;
    /** Coach, formateur, admin : lignes de la liste des apprentis. */
    apprentices: ApprenticeListItem[];
    /** Apprentis qu'un coach ou formateur peut encore ajouter. */
    assignableCount: number;
}>();

const page = usePage();
const { role } = useNavigation();
const { open: openAddGrade } = useAddGradeDialog();

const firstName = computed(
    () => page.props.auth?.user?.name?.split(/\s+/)[0] ?? '',
);

/** Coachs et formateurs suivent des apprentis ; les autres rôles voient leur propre parcours. */
const isApprentice = computed(() => role.value === 'apprentice');

const gradebook = computed(() =>
    props.tree ? createGradebook(props.tree, props.grades) : null,
);

// TODO: date de début réelle de l'apprentissage, quand elle sera en base.
const currentSemester = Math.min(
    SEMESTERS,
    trainingPeriod(DEMO_APPRENTICESHIP_START_YEAR).semester,
);

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
            >
                <template #actions>
                    <div class="home-hero__meta">
                        <span v-if="profile?.track" class="pill">
                            Filière <strong>{{ profile.track }}</strong>
                        </span>
                        <span v-if="profile" class="pill">
                            Variante
                            <strong>{{
                                profile.variant === 'mp'
                                    ? 'maturité'
                                    : 'standard'
                            }}</strong>
                        </span>
                        <span class="pill">
                            Semestre
                            <strong
                                >{{ currentSemester }} / {{ SEMESTERS }}</strong
                            >
                        </span>
                    </div>
                </template>
            </PageHeader>

            <template v-if="gradebook">
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
            />
            <SupervisorDashboard
                :apprentices="apprentices"
                :assignable-count="assignableCount"
            />
        </template>
    </PageContainer>
</template>
