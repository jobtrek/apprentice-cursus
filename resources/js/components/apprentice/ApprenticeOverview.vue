<script setup lang="ts">
import { computed } from 'vue';
import DomainChart from '@/components/gradebook/DomainChart.vue';
import HatchPattern from '@/components/gradebook/HatchPattern.vue';
import TrainingPath from '@/components/gradebook/TrainingPath.vue';
import type { Gradebook } from '@/lib/gradebook';
import type { Apprentice } from '@/types/apprentice';

/**
 * Haut de l'onglet Notes du profil : parcours de formation (moyenne de chaque
 * semestre) et moyennes par domaine, pondérées comme pour le CFC.
 */
const props = defineProps<{
    apprentice: Apprentice;
    gradebook: Gradebook;
}>();

const profile = computed(() => ({
    track: props.apprentice.track,
    variant: props.apprentice.isMp ? ('mp' as const) : ('standard' as const),
}));
</script>

<template>
    <TrainingPath :gradebook="gradebook" :profile="profile" derived />

    <DomainChart :gradebook="gradebook" />

    <HatchPattern />
</template>
