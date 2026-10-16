<script setup lang="ts">
import { Button } from '@/components/ui/button';

defineProps<{
    hasReading: boolean;
    canApprove: boolean;
    canAnalyse: boolean;
    analysing: boolean;
    saving: boolean;
}>();

defineEmits<{
    analyse: [];
    approve: [];
    deny: [];
}>();
</script>

<template>
    <template v-if="hasReading">
        <Button type="button" variant="outline" @click="$emit('deny')">
            Refuser et modifier
        </Button>
        <Button
            v-if="canApprove"
            type="button"
            :disabled="saving"
            @click="$emit('approve')"
        >
            Approuver
        </Button>
    </template>
    <Button
        v-else
        type="button"
        :disabled="!canAnalyse"
        @click="$emit('analyse')"
    >
        {{ analysing ? 'Analyse en cours…' : 'Analyser le PDF' }}
    </Button>
</template>
