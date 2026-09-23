<script setup lang="ts">
import { computed } from 'vue';
import ScoreGauge from './ScoreGauge.vue';
import { getBranchStatus, type BranchScore } from '@/data/apprenticesScores';

const props = defineProps<{
    branch: BranchScore;
}>();

const status = computed(() =>
    getBranchStatus(props.branch.score, props.branch.max),
);
</script>

<template>
    <div class="flex items-center justify-between py-4">
        <div>
            <p class="text-card-foreground font-medium">{{ branch.name }}</p>
            <p class="text-muted-foreground text-sm">
                Dernière éval · {{ branch.lastEvalLabel }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            <span class="text-sm font-medium" :class="status.class">{{
                status.label
            }}</span>
            <ScoreGauge
                :value="branch.score"
                :max="branch.max"
                :size="40"
                :stroke-width="5"
                :color="status.color"
                class="shrink-0"
            />
            <span
                class="text-card-foreground w-8 text-right text-sm font-semibold"
            >
                {{ branch.score.toFixed(1) }}
            </span>
        </div>
    </div>
</template>
