<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    BookOpenIcon,
    EllipsisIcon,
    EyeIcon,
    FolderOpenIcon,
    UserRoundIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
    SupervisorOption,
    TrainerOption,
} from '@/types/apprentice';
import ApprenticeAvatar from './ApprenticeAvatar.vue';
import AssignmentBadge from './AssignmentBadge.vue';
import AverageMeter from './AverageMeter.vue';
import StatusBadge from './StatusBadge.vue';
import TrackBadges from './TrackBadges.vue';
import SupervisorSelect from './SupervisorSelect.vue';

const props = defineProps<{
    apprentice: ApprenticeListItem;
    /** Admin local : coachs et formateurs proposés dans les cellules. Null sinon. */
    coaches: SupervisorOption[] | null;
    trainers: TrainerOption[] | null;
}>();

defineEmits<{
    /** Aperçu rapide dans le panneau latéral, sans quitter la liste. */
    preview: [apprentice: ApprenticeListItem];
}>();

const profile = (tab?: 'grades' | 'portfolio') =>
    apprentices.show(props.apprentice.id, {
        query: tab ? { tab } : undefined,
    });

const openProfile = () => router.visit(profile());

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

/** Seuls les formateurs de la filière de l'apprenti·e peuvent le suivre. */
const trainerOptions = computed(
    () =>
        props.trainers?.filter(
            (trainer) => trainer.track === props.apprentice.track,
        ) ?? null,
);
</script>

<template>
    <!-- Toute la ligne ouvre le profil ; le menu mène droit à une section. -->
    <TableRow
        class="group focus-visible:bg-muted/50 cursor-pointer focus-visible:outline-none"
        tabindex="0"
        :data-test="`apprentice-row-${apprentice.id}`"
        @click="openProfile"
        @keydown.enter.self.prevent="openProfile"
        @keydown.space.self.prevent="openProfile"
    >
        <TableCell>
            <div class="flex items-center gap-3">
                <ApprenticeAvatar
                    :name="apprentice.name"
                    :tone="situation.tone"
                />
                <div class="flex min-w-0 flex-col">
                    <span
                        class="flex items-center gap-2 font-medium group-hover:underline"
                    >
                        {{ apprentice.name }}
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
            <StatusBadge :tone="situation.tone" :label="situation.label" />
        </TableCell>

        <TableCell>
            <AverageMeter :average="apprentice.stats.average" />
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

        <TableCell v-if="trainerOptions" @click.stop @keydown.stop>
            <SupervisorSelect
                :current="current(apprentice.trainerId, apprentice.trainer)"
                :options="trainerOptions"
                :url="apprentices.trainer.update.url(apprentice.id)"
                field="trainer_id"
                none-label="Aucun formateur"
                :label="`Formateur de ${apprentice.name}`"
            />
        </TableCell>
        <TableCell v-else>
            <AssignmentBadge :value="apprentice.trainer ?? undefined" />
        </TableCell>

        <TableCell @click.stop @keydown.stop>
            <div class="flex justify-end gap-0.5">
                <Button
                    variant="ghost"
                    size="icon-sm"
                    class="opacity-0 transition-opacity group-focus-within:opacity-100 group-hover:opacity-100 focus-visible:opacity-100"
                    :aria-label="`Aperçu de ${apprentice.name}`"
                    :title="`Aperçu de ${apprentice.name}`"
                    @click="$emit('preview', apprentice)"
                >
                    <EyeIcon aria-hidden="true" />
                </Button>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :aria-label="`Actions pour ${apprentice.name}`"
                            :data-test="`apprentice-actions-${apprentice.id}`"
                        >
                            <EllipsisIcon aria-hidden="true" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-52">
                        <DropdownMenuLabel class="truncate">
                            {{ apprentice.name }}
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem as-child>
                            <Link :href="profile()">
                                <UserRoundIcon aria-hidden="true" />
                                Voir le profil
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <Link :href="profile('grades')">
                                <BookOpenIcon aria-hidden="true" />
                                Carnet de notes
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <Link :href="profile('portfolio')">
                                <FolderOpenIcon aria-hidden="true" />
                                Portfolio
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            @select="$emit('preview', apprentice)"
                        >
                            <EyeIcon aria-hidden="true" />
                            Aperçu rapide
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </TableCell>
    </TableRow>
</template>
