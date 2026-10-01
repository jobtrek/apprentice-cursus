<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { BookOpenIcon, EyeIcon, FolderOpenIcon } from '@lucide/vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { TableCell, TableRow } from '@/components/ui/table';
import { getInitials } from '@/composables/useInitials';
import type { Apprentice, ApprenticeStats } from '@/composables/useApprentices';
import { PASSING_GRADE } from '@/data/dashboard';
import apprentices from '@/routes/apprentices';
import AssignmentBadge from './AssignmentBadge.vue';

const props = defineProps<{
    apprentice: Apprentice;
    stats?: ApprenticeStats;
}>();

defineEmits<{
    /** Aperçu rapide dans le panneau latéral, sans quitter la liste. */
    preview: [apprentice: Apprentice];
}>();

const profile = (tab?: 'grades' | 'portfolio') =>
    apprentices.show(Number(props.apprentice.id), {
        query: tab ? { tab } : undefined,
    });

const openProfile = () => router.visit(profile());
</script>

<template>
    <!-- Toute la ligne ouvre le profil ; les boutons mènent droit à une section. -->
    <TableRow
        class="group focus-visible:bg-muted/50 cursor-pointer focus-visible:outline-none"
        tabindex="0"
        @click="openProfile"
        @keydown.enter.self.prevent="openProfile"
        @keydown.space.self.prevent="openProfile"
    >
        <TableCell>
            <div class="flex items-center gap-3">
                <Avatar>
                    <AvatarImage :src="apprentice.avatarUrl ?? ''" />
                    <AvatarFallback class="text-xs">
                        {{ getInitials(apprentice.name) }}
                    </AvatarFallback>
                </Avatar>
                <span class="font-medium group-hover:underline">
                    {{ apprentice.name }}
                </span>
            </div>
        </TableCell>

        <TableCell>{{ apprentice.track }}</TableCell>
        <TableCell>{{ apprentice.year }}</TableCell>

        <TableCell class="text-right tabular-nums">
            {{ stats?.grades_count ?? '—' }}
        </TableCell>
        <TableCell class="text-right font-semibold tabular-nums">
            <span
                v-if="stats?.average != null"
                :class="{ 'text-destructive': stats.average < PASSING_GRADE }"
                :title="
                    stats.average < PASSING_GRADE
                        ? 'Moyenne insuffisante'
                        : undefined
                "
            >
                {{ stats.average.toFixed(1) }}
            </span>
            <span v-else class="text-muted-foreground font-normal">—</span>
        </TableCell>
        <TableCell class="text-muted-foreground tabular-nums">
            {{ stats?.last_grade_date ?? '—' }}
        </TableCell>

        <TableCell class="hidden xl:table-cell">
            <AssignmentBadge :value="apprentice.coach" />
        </TableCell>

        <TableCell class="hidden xl:table-cell">
            <AssignmentBadge :value="apprentice.trainer" />
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
