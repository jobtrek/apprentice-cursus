<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { BookOpenIcon, EyeIcon, FolderOpenIcon } from '@lucide/vue';
import { computed } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { TableCell, TableRow } from '@/components/ui/table';
import { getInitials } from '@/composables/useInitials';
import apprentices from '@/routes/apprentices';
import type {
    ApprenticeListItem,
    SupervisorOption,
    TrainerOption,
} from '@/types/apprentice';
import AssignmentBadge from './AssignmentBadge.vue';
import AverageValue from './AverageValue.vue';
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
    <!-- Toute la ligne ouvre le profil ; les boutons mènent droit à une section. -->
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
                <Avatar>
                    <AvatarFallback class="text-xs">
                        {{ getInitials(apprentice.name) }}
                    </AvatarFallback>
                </Avatar>
                <span class="font-medium group-hover:underline">
                    {{ apprentice.name }}
                </span>
                <Badge v-if="!apprentice.isActive" variant="outline">
                    Inactif
                </Badge>
            </div>
        </TableCell>

        <TableCell>
            <Badge v-if="apprentice.track" variant="outline">
                {{ apprentice.track }}
            </Badge>
            <span v-else class="text-muted-foreground">—</span>
        </TableCell>

        <TableCell class="text-right tabular-nums">
            {{ apprentice.stats.grades_count }}
        </TableCell>
        <TableCell class="text-right">
            <AverageValue :average="apprentice.stats.average" />
        </TableCell>
        <TableCell class="text-muted-foreground tabular-nums">
            {{ apprentice.stats.last_grade_date ?? '—' }}
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

        <TableCell
            v-if="trainerOptions"
            class="hidden lg:table-cell"
            @click.stop
            @keydown.stop
        >
            <SupervisorSelect
                :current="current(apprentice.trainerId, apprentice.trainer)"
                :options="trainerOptions"
                :url="apprentices.trainer.update.url(apprentice.id)"
                field="trainer_id"
                none-label="Aucun formateur"
                :label="`Formateur de ${apprentice.name}`"
            />
        </TableCell>
        <TableCell v-else class="hidden lg:table-cell">
            <AssignmentBadge :value="apprentice.trainer ?? undefined" />
        </TableCell>

        <TableCell @click.stop>
            <div class="flex justify-end gap-1">
                <Button
                    variant="ghost"
                    size="icon-sm"
                    :aria-label="`Aperçu de ${apprentice.name}`"
                    :title="`Aperçu de ${apprentice.name}`"
                    @click="$emit('preview', apprentice)"
                >
                    <EyeIcon aria-hidden="true" />
                </Button>
                <Button as-child variant="ghost" size="icon-sm">
                    <Link
                        :href="profile('grades')"
                        :aria-label="`Carnet de notes de ${apprentice.name}`"
                        :title="`Carnet de notes de ${apprentice.name}`"
                    >
                        <BookOpenIcon aria-hidden="true" />
                    </Link>
                </Button>
                <Button as-child variant="ghost" size="icon-sm">
                    <Link
                        :href="profile('portfolio')"
                        :aria-label="`Portfolio de ${apprentice.name}`"
                        :title="`Portfolio de ${apprentice.name}`"
                    >
                        <FolderOpenIcon aria-hidden="true" />
                    </Link>
                </Button>
            </div>
        </TableCell>
    </TableRow>
</template>
