<script setup lang="ts">
import ScoreGauge from "./ScoreGauge.vue";

defineProps<{
    average: number;
    max: number;
}>();
</script>

<template>
    <div
        class="rounded-xl border bg-card px-6 py-8 flex flex-col items-center gap-1"
    >
        <p class="text-sm text-muted-foreground">Moyenne générale</p>

        <ScoreGauge
            :value="average"
            :max="max"
            :size="170"
            :stroke-width="14"
            class="my-2"
        >
            <span class="text-5xl font-semibold text-card-foreground">
                {{ average.toFixed(1) }}
            </span>
        </ScoreGauge>

        <p class="text-sm text-muted-foreground">
            sur une échelle de 1 à {{ max }}
        </p>
    </div>
</template>
