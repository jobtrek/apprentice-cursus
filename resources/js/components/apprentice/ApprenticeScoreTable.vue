<script setup lang="ts">
import BranchScoreRow from "./BranchScoreRow.vue";
import type { BranchScore } from "@/data_2/apprenticesScores";

defineProps<{
    branches: BranchScore[];
}>();
</script>

<template>
    <div>
        <p class="mb-2 text-sm text-muted-foreground">Moyennes par branche</p>
        <div class="divide-y divide-border">
            <BranchScoreRow
                v-for="branch in branches"
                :key="branch.id"
                :branch="branch"
            />
        </div>
    </div>
</template>
