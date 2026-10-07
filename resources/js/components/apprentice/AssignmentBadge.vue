<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

defineProps<{
    value?: string;
}>();
</script>

<template>
    <!-- Signalé sans alarmer : un superviseur manquant n'est pas une erreur. -->
    <Badge
        v-if="!value"
        variant="outline"
        class="text-muted-foreground border-dashed font-normal"
    >
        Non assigné
    </Badge>
    <span v-else>{{ value }}</span>
</template>
