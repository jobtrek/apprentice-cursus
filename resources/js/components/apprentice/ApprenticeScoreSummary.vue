<script setup lang="ts">
import { computed } from 'vue';
import ScoreGauge from './ScoreGauge.vue';
import { getBranchStatus } from '@/data/apprenticesScores';

const props = defineProps<{
    average: number;
    max: number;
}>();

const status = computed(() => getBranchStatus(props.average, props.max));
</script>

<template>
    <div
        class="bg-card flex flex-col items-center gap-1 rounded-xl border px-6 py-8"
    >
        <p class="text-muted-foreground text-sm">Moyenne générale</p>

        <ScoreGauge
            :value="average"
            :max="max"
            :size="170"
            :stroke-width="14"
            :color="status.color"
            class="my-2"
        >
            <span
                class="text-card-foreground text-3xl font-semibold tabular-nums"
            >
                {{ average.toFixed(1) }}
            </span>
        </ScoreGauge>

        <p class="text-muted-foreground text-sm">
            sur une échelle de 1 à {{ max }}
        </p>
    </div>
</template>
