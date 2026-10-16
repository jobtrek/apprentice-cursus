<script setup lang="ts">
import { computed } from 'vue';
import DomainChart from '@/components/gradebook/DomainChart.vue';
import HatchPattern from '@/components/gradebook/HatchPattern.vue';
import TrainingPath from '@/components/gradebook/TrainingPath.vue';
import type { Averages } from '@/lib/gradebook';
import type { Apprentice } from '@/types/apprentice';

/**
 * Haut de l'onglet Notes du profil : parcours de formation (moyenne de chaque
 * semestre) et moyennes par domaine, pondérées comme pour le CFC.
 */
const props = defineProps<{
    apprentice: Apprentice;
    averages: Averages;
}>();

const profile = computed(() => ({
    track: props.apprentice.track,
    variant: props.apprentice.isMp ? ('mp' as const) : ('standard' as const),
}));
</script>

<template>
    <TrainingPath :averages="averages" :profile="profile" derived />

    <DomainChart :averages="averages" />

    <HatchPattern />
</template>
