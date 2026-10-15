<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { StatItem } from '@/components/page';
import { Badge } from '@/components/ui/badge';
import { relativeDate, situationOf, yearLabel } from '@/lib/apprentice';
import apprentices from '@/routes/apprentices';
import type { ApprenticeListItem, AssignSelfAs } from '@/types/apprentice';
import ApprenticeAvatar from './ApprenticeAvatar.vue';
import AssignmentBadge from './AssignmentBadge.vue';
import AverageMeter from './AverageMeter.vue';
import StatusBadge from './StatusBadge.vue';
import TrackBadges from './TrackBadges.vue';

const props = defineProps<{
    apprentice: ApprenticeListItem;
    /** Rôle de l'utilisateur : ce superviseur-là est toujours lui-même. */
    ownRole?: AssignSelfAs | null;
}>();

const stale = computed(
    () =>
        props.apprentice.stats.grades_count > 0 && props.apprentice.stats.stale,
);

const situation = computed(() => situationOf(props.apprentice));
</script>

<template>
    <!--
        Version carte d'une ligne du tableau. Le nom couvre toute la carte
        (lien étiré) : toute la carte ouvre le profil.
    -->
    <article
        class="bg-card focus-within:ring-ring/50 relative flex min-w-0 flex-col gap-4 rounded-xl border p-4 shadow-xs transition-all focus-within:ring-[3px] hover:-translate-y-0.5 hover:shadow-md"
        :data-test="`apprentice-card-${apprentice.id}`"
    >
        <div class="flex items-start gap-3">
            <ApprenticeAvatar
                :name="apprentice.name"
                :tone="situation.tone"
                class="size-11"
                fallback-class="text-sm"
            />

            <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                <Link
                    :href="apprentices.show(apprentice.id)"
                    class="truncate font-semibold outline-none after:absolute after:inset-0 after:rounded-xl hover:underline"
                >
                    {{ apprentice.name }}
                </Link>
                <span class="text-muted-foreground text-xs">
                    {{
                        apprentice.year
                            ? yearLabel(apprentice.year)
                            : 'Année inconnue'
                    }}
                </span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-1.5">
            <StatusBadge :tone="situation.tone" :label="situation.label" />
            <TrackBadges :track="apprentice.track" :is-mp="apprentice.isMp" />
            <Badge v-if="!apprentice.isActive" variant="outline">
                Inactif
            </Badge>
        </div>

        <div class="bg-muted/40 flex flex-col gap-2 rounded-lg p-3">
            <div
                class="flex flex-wrap items-center justify-between gap-x-2 text-xs"
            >
                <span class="text-muted-foreground font-medium">Moyenne</span>
                <span class="text-muted-foreground tabular-nums">
                    {{ apprentice.stats.grades_count }}
                    note{{ apprentice.stats.grades_count > 1 ? 's' : '' }}
                    <template v-if="apprentice.stats.last_grade_date">
                        ·
                        <span
                            :class="{ 'text-warning': stale }"
                            :title="apprentice.stats.last_grade_date"
                        >
                            {{ relativeDate(apprentice.stats.last_grade_date) }}
                        </span>
                    </template>
                </span>
            </div>
            <AverageMeter :average="apprentice.stats.average" class="text-lg" />
        </div>

        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
            <StatItem v-if="ownRole !== 'coach'" label="Coach">
                <AssignmentBadge :value="apprentice.coach ?? undefined" />
            </StatItem>
            <StatItem label="Formateur">
                <AssignmentBadge :value="apprentice.trainer ?? undefined" />
            </StatItem>
        </div>
    </article>
</template>
