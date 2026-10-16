<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { TableCell, TableRow } from '@/components/ui/table';
import {
    hasNoRecentGrade,
    relativeDate,
    situationOf,
    STALE_AFTER_DAYS,
    yearLabel,
} from '@/lib/apprentice';
import apprentices from '@/routes/apprentices';
import type {
    ApprenticeListItem,
    AssignSelfAs,
    SupervisorOption,
} from '@/types/apprentice';
import ApprenticeAvatar from './ApprenticeAvatar.vue';
import AssignmentBadge from './AssignmentBadge.vue';
import AverageValue from './AverageValue.vue';
import TrackBadges from './TrackBadges.vue';
import SupervisorSelect from './SupervisorSelect.vue';

const props = defineProps<{
    apprentice: ApprenticeListItem;
    /** Admin local : coachs proposés dans les cellules. Null sinon. */
    coaches: SupervisorOption[] | null;
    /** Rôle de l'utilisateur : sa colonne est masquée (toujours lui-même). */
    ownRole?: AssignSelfAs | null;
}>();

const openProfile = () => router.visit(apprentices.show(props.apprentice.id));

/** Des notes, mais aucune récente : « Aucune note » est signalé ailleurs. */
const stale = computed(
    () =>
        props.apprentice.stats.grades_count > 0 &&
        hasNoRecentGrade(props.apprentice.stats.last_grade_date),
);

const situation = computed(() => situationOf(props.apprentice));

/** Superviseur actuel au format des options du select. */
const current = (
    id: number | null,
    name: string | null,
): SupervisorOption | null =>
    id === null || name === null ? null : { id, name };
</script>

<template>
    <!-- Toute la ligne ouvre le profil. -->
    <TableRow
        class="group has-[a:focus-visible]:bg-muted/50 cursor-pointer"
        :data-test="`apprentice-row-${apprentice.id}`"
        @click="openProfile"
    >
        <TableCell>
            <div class="flex items-center gap-3">
                <ApprenticeAvatar
                    :name="apprentice.name"
                    :tone="situation.tone"
                />
                <div class="relative flex min-w-0 flex-col">
                    <span class="flex items-center gap-2 font-medium">
                        <Link
                            :href="apprentices.show(apprentice.id)"
                            class="group-hover:underline after:absolute after:inset-0"
                            @click.stop
                        >
                            {{ apprentice.name }}
                        </Link>
                        <Badge v-if="!apprentice.isActive" variant="outline">
                            Inactif
                        </Badge>
                    </span>
                    <span
                        class="text-muted-foreground text-xs"
                        :title="
                            apprentice.year
                                ? 'Déduite du dernier semestre noté'
                                : undefined
                        "
                    >
                        {{
                            apprentice.year
                                ? yearLabel(apprentice.year)
                                : 'Année inconnue'
                        }}
                    </span>
                </div>
            </div>
        </TableCell>

        <TableCell>
            <TrackBadges :track="apprentice.track" :is-mp="apprentice.isMp" />
        </TableCell>

        <TableCell>
            <AverageValue :average="apprentice.stats.average" />
        </TableCell>

        <TableCell>
            <div class="flex flex-col">
                <span class="tabular-nums">
                    {{ apprentice.stats.grades_count }}
                    note{{ apprentice.stats.grades_count > 1 ? 's' : '' }}
                </span>
                <span
                    v-if="apprentice.stats.last_grade_date"
                    class="text-xs"
                    :class="stale ? 'text-warning' : 'text-muted-foreground'"
                    :title="
                        stale
                            ? `Aucune note depuis plus de ${STALE_AFTER_DAYS} jours (${apprentice.stats.last_grade_date})`
                            : apprentice.stats.last_grade_date
                    "
                >
                    {{ relativeDate(apprentice.stats.last_grade_date) }}
                </span>
            </div>
        </TableCell>

        <template v-if="ownRole !== 'coach'">
            <TableCell v-if="coaches" @click.stop @keydown.stop>
                <SupervisorSelect
                    :current="current(apprentice.coachId, apprentice.coach)"
                    :options="coaches"
                    :url="apprentices.coach.update.url(apprentice.id)"
                    field="coach_id"
                    none-label="Aucun coach"
                    :label="`Coach de ${apprentice.name}`"
                />
            </TableCell>
            <TableCell v-else>
                <AssignmentBadge :value="apprentice.coach ?? undefined" />
            </TableCell>
        </template>

        <!-- Les formateurs suivent le plan entier : pas de choix par apprenti·e. -->
        <!-- Largeur bornée : une longue liste de formateurs ferait défiler le tableau. -->
        <TableCell class="max-w-56" :title="apprentice.trainer ?? undefined">
            <AssignmentBadge
                :value="apprentice.trainer ?? undefined"
                class="max-w-full"
            />
        </TableCell>
    </TableRow>
</template>
