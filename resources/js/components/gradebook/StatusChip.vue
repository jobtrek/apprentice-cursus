<script setup lang="ts">
import { CheckIcon, TriangleAlertIcon, XIcon } from '@lucide/vue';
import { computed } from 'vue';
import { STATUS_LABELS } from '@/lib/gradebook';
import { gradeLevel } from '@/lib/grade-scale';

const props = defineProps<{ value: number }>();

const GLYPHS = { good: CheckIcon, warn: TriangleAlertIcon, bad: XIcon };

const level = computed(() => gradeLevel(props.value));
</script>

<template>
    <!-- Validé / Suffisant / À risque : l'icône double la couleur. -->
    <span :class="`status status--${level}`">
        <component :is="GLYPHS[level]" aria-hidden="true" />
        {{ STATUS_LABELS[level] }}
    </span>
</template>
