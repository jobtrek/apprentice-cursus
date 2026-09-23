<script setup lang="ts">
import { StatItem } from '@/components/page';
import AssignmentBadge from './AssignmentBadge.vue';

defineProps<{
    coach?: string;
    formateur?: string;
}>();
</script>

<template>
    <div class="grid grid-cols-2 gap-4">
        <StatItem label="Coach">
            <AssignmentBadge :value="coach" />
        </StatItem>

        <StatItem label="Formateur">
            <AssignmentBadge :value="formateur" />
        </StatItem>
    </div>
</template>
