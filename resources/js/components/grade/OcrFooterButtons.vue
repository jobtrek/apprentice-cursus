<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { ScanStatus } from '@/types/scan';

defineProps<{
    scanStatus: ScanStatus;
    saving: boolean;
}>();

defineEmits<{
    analyse: [];
    approve: [];
    deny: [];
}>();
</script>

<template>
    <template v-if="scanStatus === 'needsEdit' || scanStatus === 'approvable'">
        <Button type="button" variant="outline" @click="$emit('deny')">
            Refuser et modifier
        </Button>
        <Button
            v-if="scanStatus === 'approvable'"
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
        :disabled="scanStatus !== 'ready'"
        @click="$emit('analyse')"
    >
        {{
            scanStatus === 'analysing' ? 'Analyse en cours…' : 'Analyser le PDF'
        }}
    </Button>
</template>
