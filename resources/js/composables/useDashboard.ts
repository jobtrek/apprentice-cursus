import { computed, ref } from 'vue';
import dashboardData from '@/data/dashboard.json';
import type { DashboardData } from '@/types/dashboard';

const data = ref<DashboardData>(dashboardData as DashboardData);

/** Moyenne arrondie au dixième, comme les notes. */
export const average = (values: number[]): number =>
    values.length === 0
        ? 0
        : Math.round(
              (values.reduce((sum, value) => sum + value, 0) / values.length) *
                  10,
          ) / 10;

/** `+0.3`, `-0.5`, ou `—` quand l'écart est inconnu. */
export const formatDelta = (delta: number | null): string =>
    delta === null ? '—' : `${delta > 0 ? '+' : ''}${delta.toFixed(1)}`;

export const useDashboard = () => {
    const scale = computed(() => data.value.scale);
    const apprentice = computed(() => data.value.apprentice);
    const coach = computed(() => data.value.coach);

    /**
     * Les trois conditions `>= 4` de l'ordonnance. Une seule non remplie fait
     * échouer le CFC, quelle que soit la moyenne générale.
     */
    const blockingRequirements = computed(() =>
        apprentice.value.requirements.filter(
            (requirement) =>
                ['global', 'tpi', 'informatique'].includes(requirement.id) &&
                requirement.score < scale.value.passing,
        ),
    );

    const branchesAtRisk = computed(() =>
        apprentice.value.branches.filter(
            (branch) => branch.score < scale.value.passing,
        ),
    );

    const groupAverage = computed(() =>
        average(coach.value.apprentices.map((item) => item.average)),
    );

    const apprenticesAtRisk = computed(() =>
        coach.value.apprentices.filter(
            (item) => item.average < scale.value.passing,
        ),
    );

    /** Baisse marquée depuis la période précédente : à regarder en priorité. */
    const apprenticesDropping = computed(() =>
        [...coach.value.apprentices]
            .filter((item) => item.delta < 0)
            .sort((a, b) => a.delta - b.delta),
    );

    return {
        scale,
        apprentice,
        coach,
        blockingRequirements,
        branchesAtRisk,
        groupAverage,
        apprenticesAtRisk,
        apprenticesDropping,
    };
};
