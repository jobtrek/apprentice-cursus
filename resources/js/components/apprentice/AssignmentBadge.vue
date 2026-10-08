<script setup lang="ts">
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { getInitials } from '@/composables/useInitials';

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
    <span v-else class="inline-flex min-w-0 items-center gap-2">
        <Avatar class="size-6">
            <AvatarFallback
                class="bg-secondary text-secondary-foreground text-[10px] font-medium"
            >
                {{ getInitials(value) }}
            </AvatarFallback>
        </Avatar>
        <span class="truncate">{{ value }}</span>
    </span>
</template>
