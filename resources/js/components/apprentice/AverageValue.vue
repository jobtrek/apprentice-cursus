<script setup lang="ts">
import { computed } from 'vue';
import { averageStatus } from '@/lib/apprentice';

const props = defineProps<{
    /** Null sans note : affiche un tiret. */
    average: number | null;
}>();

const status = computed(() =>
    props.average === null ? null : averageStatus(props.average),
);
</script>

<template>
    <span
        v-if="average !== null && status"
        class="font-semibold tabular-nums"
        :class="status.class"
        :title="`Moyenne ${status.label.toLowerCase()}`"
    >
        {{ average.toFixed(1) }}
        <span class="sr-only">({{ status.label }})</span>
    </span>
    <span v-else class="text-muted-foreground" title="Aucune note">—</span>
</template>
