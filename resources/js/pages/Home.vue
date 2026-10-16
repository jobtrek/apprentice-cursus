<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { PlusIcon, UserPlusIcon } from '@lucide/vue';
import { computed } from 'vue';
import SupervisorDashboard from '@/components/dashboard/SupervisorDashboard.vue';
import AddGradeDialog from '@/components/grade/AddGradeDialog.vue';
import ChartCard from '@/components/gradebook/ChartCard.vue';
import DomainChart from '@/components/gradebook/DomainChart.vue';
import HatchPattern from '@/components/gradebook/HatchPattern.vue';
import RecentGrades from '@/components/gradebook/RecentGrades.vue';
import StatTiles from '@/components/gradebook/StatTiles.vue';
import TrainingPath from '@/components/gradebook/TrainingPath.vue';
import { PageContainer, PageHeader } from '@/components/page';
import { Button } from '@/components/ui/button';
import { useAddGradeDialog } from '@/composables/useAddGradeDialog';
import { useNavigation } from '@/composables/useNavigation';
import type { Averages, GradeTree } from '@/lib/gradebook';
import { apprentisdashboard } from '@/routes';
import gradeRoutes from '@/routes/grades';
import type {
    ApprenticeListItem,
    MonthlyActivity,
    SupervisedGrade,
} from '@/types/apprentice';
import type { Grade } from '@/types/grade';

const props = defineProps<{
    grades: Grade[];
    tree: GradeTree | null;
    averages: Averages | null;
    profile: { track: string | null; variant: 'standard' | 'mp' } | null;
    /** Coach, formateur, admin : lignes de la liste des apprentis. */
    apprentices: ApprenticeListItem[];
    /** Apprentis qu'un coach ou formateur peut encore ajouter. */
    assignableCount: number;
    /** Coach, formateur, admin : dernières notes des apprentis suivis. */
    latestGrades: SupervisedGrade[];
    /** Coach, formateur, admin : notes par mois sur 12 mois. */
    activity: MonthlyActivity[];
    can: {
        /** Coach : s'ajoute des apprentis, dont il devient le coach. */
        assignSelf: boolean;
    };
}>();

const page = usePage();
const { role } = useNavigation();
const { open: openAddGrade } = useAddGradeDialog();

const firstName = computed(
    () => page.props.auth?.user?.name?.split(/\s+/)[0] ?? '',
);

/** Coachs et formateurs suivent des apprentis ; les autres rôles voient leur propre parcours. */
const isApprentice = computed(() => role.value === 'apprentice');
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
                    <Button data-test="add-grade-button" @click="openAddGrade">
                        <PlusIcon aria-hidden="true" />
                        Ajouter une note
                    </Button>
                </template>
            </PageHeader>

            <template v-if="averages">
                <TrainingPath :averages="averages" :profile="profile" derived />

                <StatTiles :averages="averages" :grades="grades" />

                <!-- Les moyennes par semestre sont dans le parcours. -->
                <div class="chart-grid">
                    <DomainChart :averages="averages">
                        <template #link>
                            <Link
                                :href="gradeRoutes.dashboard()"
                                class="chart-card__link"
                            >
                                Voir le carnet de notes →
                            </Link>
                        </template>
                    </DomainChart>
                    <ChartCard
                        label-id="t-recent"
                        title="Dernières notes"
                        description="Les 5 plus récentes"
                    >
                        <RecentGrades :grades="grades" />
                    </ChartCard>
                </div>
            </template>

            <HatchPattern />
            <AddGradeDialog :tree="tree" />
        </template>

        <template v-else>
            <PageHeader
                :title="firstName ? `Bonjour ${firstName}` : 'Accueil'"
                description="Voici où en sont les apprentis que vous suivez."
            >
                <!-- L'ajout se fait depuis la liste, qui a la fenêtre de choix. -->
                <template
                    v-if="can.assignSelf && apprentices.length > 0"
                    #actions
                >
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
