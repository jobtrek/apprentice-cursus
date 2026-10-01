<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    BookOpenIcon,
    EyeIcon,
    FolderOpenIcon,
    UserPlusIcon,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { TableCell, TableRow } from '@/components/ui/table';
import { getInitials } from '@/composables/useInitials';
import { PASSING_GRADE } from '@/data/dashboard';
import apprentices from '@/routes/apprentices';
import type { ApprenticeListItem, SupervisorOption } from '@/types/apprentice';
import AssignmentBadge from './AssignmentBadge.vue';
import SupervisorSelect from './SupervisorSelect.vue';

const props = defineProps<{
    apprentice: ApprenticeListItem;
    /** Admin local : coachs proposés dans la cellule. Null sinon. */
    coaches: SupervisorOption[] | null;
}>();

defineEmits<{
    /** Aperçu rapide dans le panneau latéral, sans quitter la liste. */
    preview: [apprentice: ApprenticeListItem];
}>();

const profile = (tab?: 'grades' | 'portfolio') =>
    apprentices.show(props.apprentice.id, {
        query: tab ? { tab } : undefined,
    });

/** Un coach voit tous les apprentis mais n'ouvre que les siens. */
const openProfile = () => {
    if (props.apprentice.canView) {
        router.visit(profile());
    }
};

const currentCoach = computed<SupervisorOption | null>(() =>
    props.apprentice.coachId === null || props.apprentice.coach === null
        ? null
        : { id: props.apprentice.coachId, name: props.apprentice.coach },
);

const assigning = ref(false);

/** Le coach connecté devient le coach de cet·te apprenti·e sans coach. */
function assignSelf(): void {
    router.post(
        apprentices.assign.url(props.apprentice.id),
        {},
        {
            preserveScroll: true,
            onStart: () => (assigning.value = true),
            onFinish: () => (assigning.value = false),
        },
    );
}
</script>

<template>
    <!-- Toute la ligne ouvre le profil ; les boutons mènent droit à une section. -->
    <TableRow
        :class="
            apprentice.canView &&
            'group focus-visible:bg-muted/50 cursor-pointer focus-visible:outline-none'
        "
        :tabindex="apprentice.canView ? 0 : undefined"
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
                <span
                    class="font-medium"
                    :class="apprentice.canView && 'group-hover:underline'"
                >
                    {{ apprentice.name }}
                </span>
                <Badge v-if="!apprentice.isActive" variant="outline">
                    Inactif
                </Badge>
            </div>
        </TableCell>

        <TableCell>{{ apprentice.track ?? '—' }}</TableCell>

        <template v-if="apprentice.stats">
            <TableCell class="text-right tabular-nums">
                {{ apprentice.stats.grades_count }}
            </TableCell>
            <TableCell class="text-right font-semibold tabular-nums">
                <span
                    v-if="apprentice.stats.average !== null"
                    :class="{
                        'text-destructive':
                            apprentice.stats.average < PASSING_GRADE,
                    }"
                    :title="
                        apprentice.stats.average < PASSING_GRADE
                            ? 'Moyenne insuffisante'
                            : undefined
                    "
                >
                    {{ apprentice.stats.average.toFixed(1) }}
                </span>
                <span v-else class="text-muted-foreground font-normal">—</span>
            </TableCell>
            <TableCell class="text-muted-foreground tabular-nums">
                {{ apprentice.stats.last_grade_date ?? '—' }}
            </TableCell>
        </template>
        <!-- Apprenti·e d'un autre coach : notes non accessibles. -->
        <template v-else>
            <TableCell class="text-muted-foreground text-right">—</TableCell>
            <TableCell class="text-muted-foreground text-right">—</TableCell>
            <TableCell class="text-muted-foreground">—</TableCell>
        </template>

        <TableCell v-if="coaches" @click.stop @keydown.stop>
            <SupervisorSelect
                :current="currentCoach"
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

        <TableCell class="hidden lg:table-cell">
            <AssignmentBadge :value="apprentice.trainer ?? undefined" />
        </TableCell>

        <TableCell @click.stop>
            <div class="flex justify-end gap-1">
                <Button
                    v-if="apprentice.canAssign"
                    size="sm"
                    variant="outline"
                    :disabled="assigning"
                    :data-test="`assign-apprentice-${apprentice.id}`"
                    @click="assignSelf"
                >
                    <UserPlusIcon aria-hidden="true" />
                    M'attribuer
                </Button>
                <template v-if="apprentice.canView">
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
                </template>
            </div>
        </TableCell>
    </TableRow>
</template>
