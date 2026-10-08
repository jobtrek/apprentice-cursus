<script setup lang="ts">
import { computed } from 'vue';
import { GRADE_MAX } from '@/constants/constants';
import { PASSING_GRADE } from '@/data/dashboard';
import { averageStatus, TONES } from '@/lib/apprentice';
import { cn } from '@/lib/utils';

const props = defineProps<{
    /** Null sans note : affiche un tiret et une jauge vide. */
    average: number | null;
    class?: string;
}>();

const status = computed(() =>
    props.average === null ? null : averageStatus(props.average),
);

const percent = (value: number) => `${(value / GRADE_MAX) * 100}%`;
</script>

<template>
    <!-- Moyenne et sa jauge sur l'échelle 1–6, repère au seuil de réussite. -->
    <div :class="cn('flex min-w-28 items-center gap-3', props.class)">
        <span
            v-if="average !== null && status"
            class="w-8 text-right font-semibold tabular-nums"
            :class="status.class"
            :title="`Moyenne ${status.label.toLowerCase()}`"
        >
            {{ average.toFixed(1) }}
            <span class="sr-only">({{ status.label }})</span>
        </span>
        <span
            v-else
            class="text-muted-foreground w-8 text-right"
            title="Aucune note"
        >
            —
        </span>

        <div
            class="bg-muted relative h-1.5 flex-1 rounded-full"
            aria-hidden="true"
        >
            <div
                v-if="average !== null && status"
                class="h-full rounded-full transition-[width]"
                :class="TONES[status.tone].bar"
                :style="{ width: percent(average) }"
            />
            <span
                class="bg-foreground/30 absolute -top-0.5 h-2.5 w-px"
                :style="{ left: percent(PASSING_GRADE) }"
            />
        </div>
    </div>
</template>
