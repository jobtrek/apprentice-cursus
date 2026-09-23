<script setup lang="ts">
import type { BranchScore } from '@/data/apprenticesScores';
import BranchScoreRow from './BranchScoreRow.vue';

defineProps<{
    branches: BranchScore[];
}>();
</script>

<template>
    <div class="flex flex-col gap-2">
        <h3 class="text-sm font-semibold">Moyennes par branche</h3>
        <div class="divide-y">
            <BranchScoreRow
                v-for="branch in branches"
                :key="branch.id"
                :branch="branch"
            />
        </div>
    </div>
</template>
