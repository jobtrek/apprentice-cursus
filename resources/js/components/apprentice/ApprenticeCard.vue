<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpenIcon, EyeIcon, FolderOpenIcon } from '@lucide/vue';
import { computed } from 'vue';
import { StatItem } from '@/components/page';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import { hasNoRecentGrade, yearLabel } from '@/lib/apprentice';
import apprentices from '@/routes/apprentices';
import type { ApprenticeListItem } from '@/types/apprentice';
import AssignmentBadge from './AssignmentBadge.vue';
import AverageValue from './AverageValue.vue';

const props = defineProps<{
    apprentice: ApprenticeListItem;
}>();

defineEmits<{
    preview: [apprentice: ApprenticeListItem];
}>();

const profile = (tab?: 'grades' | 'portfolio') =>
    apprentices.show(props.apprentice.id, {
        query: tab ? { tab } : undefined,
    });

const stale = computed(
    () =>
        props.apprentice.stats.grades_count > 0 &&
        hasNoRecentGrade(props.apprentice.stats.last_grade_date),
);
</script>

<template>
    <!--
        Version petit écran d'une ligne du tableau. Le nom couvre toute la
        carte (lien étiré) ; les boutons du pied restent cliquables au-dessus.
    -->
    <article
        class="bg-card hover:bg-muted/30 focus-within:ring-ring/50 relative flex flex-col gap-4 rounded-xl border p-4 transition-colors focus-within:ring-[3px]"
        :data-test="`apprentice-card-${apprentice.id}`"
    >
        <div class="flex items-start gap-3">
            <Avatar class="size-10">
                <AvatarFallback class="text-xs font-medium">
                    {{ getInitials(apprentice.name) }}
                </AvatarFallback>
            </Avatar>

            <div class="flex min-w-0 flex-1 flex-col gap-1">
                <Link
                    :href="profile()"
                    class="truncate font-medium outline-none after:absolute after:inset-0 after:rounded-xl hover:underline"
                >
                    {{ apprentice.name }}
                </Link>
                <div class="flex flex-wrap items-center gap-1.5">
                    <Badge v-if="apprentice.track" variant="outline">
                        {{ apprentice.track }}
                    </Badge>
                    <Badge
                        v-if="apprentice.isMp"
                        variant="secondary"
                        title="Maturité professionnelle"
                    >
                        MP
                    </Badge>
                    <span
                        v-if="apprentice.year"
                        class="text-muted-foreground text-xs"
                    >
                        {{ yearLabel(apprentice.year) }}
                    </span>
                    <Badge v-if="!apprentice.isActive" variant="outline">
                        Inactif
                    </Badge>
                </div>
            </div>

            <div class="flex flex-col items-end">
                <span class="text-lg leading-tight">
                    <AverageValue :average="apprentice.stats.average" />
                </span>
                <span class="text-muted-foreground text-xs">Moyenne</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
            <StatItem label="Notes">
                <span class="tabular-nums">
                    {{ apprentice.stats.grades_count }}
                </span>
            </StatItem>
            <StatItem label="Dernière note">
                <span class="tabular-nums" :class="{ 'text-warning': stale }">
                    {{ apprentice.stats.last_grade_date ?? '—' }}
                </span>
            </StatItem>
            <StatItem label="Coach">
                <AssignmentBadge :value="apprentice.coach ?? undefined" />
            </StatItem>
            <StatItem label="Formateur">
                <AssignmentBadge :value="apprentice.trainer ?? undefined" />
            </StatItem>
        </div>

        <div class="relative z-10 -mx-1 -mb-1 flex gap-1 border-t pt-3">
            <Button
                variant="ghost"
                size="sm"
                @click="$emit('preview', apprentice)"
            >
                <EyeIcon aria-hidden="true" />
                Aperçu
            </Button>
            <Button as-child variant="ghost" size="sm">
                <Link :href="profile('grades')">
                    <BookOpenIcon aria-hidden="true" />
                    Notes
                </Link>
            </Button>
            <Button as-child variant="ghost" size="sm">
                <Link :href="profile('portfolio')">
                    <FolderOpenIcon aria-hidden="true" />
                    Portfolio
                </Link>
            </Button>
        </div>
    </article>
</template>
