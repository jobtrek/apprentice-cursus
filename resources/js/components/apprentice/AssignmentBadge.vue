<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

defineProps<{
    value?: string | null;
}>();
</script>

<template>
    <Badge v-if="!value" variant="destructive">Non assigné</Badge>
    <span v-else>{{ value }}</span>
</template>
