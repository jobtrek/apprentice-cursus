<script setup lang="ts">
import { StatItem } from '@/components/page';
import AssignmentBadge from './AssignmentBadge.vue';

defineProps<{
    coach?: string | null;
}>();
</script>

<template>
    <div class="grid grid-cols-1 gap-4">
        <StatItem label="Coach">
            <AssignmentBadge :value="coach" />
        </StatItem>
    </div>
</template>
