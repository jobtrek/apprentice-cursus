<script setup lang="ts">
import { CheckIcon, TriangleAlertIcon, XIcon } from '@lucide/vue';
import { computed } from 'vue';
import { status } from '@/lib/gradebook';

const props = defineProps<{ value: number }>();

const GLYPHS = { good: CheckIcon, warn: TriangleAlertIcon, bad: XIcon };

const state = computed(() => status(props.value));
</script>

<template>
    <!-- Validé / Suffisant / À risque : l'icône double la couleur. -->
    <span :class="`status status--${state[0]}`">
        <component :is="GLYPHS[state[0]]" aria-hidden="true" />
        {{ state[1] }}
    </span>
</template>
