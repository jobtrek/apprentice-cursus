<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { TableCell, TableRow } from '@/components/ui/table';
import { getInitials } from '@/composables/useInitials';
import type { Apprentice } from '@/composables/useApprentices';
import AssignmentBadge from './AssignmentBadge.vue';

defineProps<{
    apprentice: Apprentice;
}>();

defineEmits<{
    select: [apprentice: Apprentice];
}>();
</script>

<template>
    <TableRow
        class="focus-visible:bg-muted/50 cursor-pointer focus-visible:outline-none"
        tabindex="0"
        @click="$emit('select', apprentice)"
        @keydown.enter.prevent="$emit('select', apprentice)"
        @keydown.space.prevent="$emit('select', apprentice)"
    >
        <TableCell>
            <div class="flex items-center gap-3">
                <Avatar>
                    <AvatarImage :src="apprentice.avatarUrl ?? ''" />
                    <AvatarFallback class="text-xs">
                        {{ getInitials(apprentice.name) }}
                    </AvatarFallback>
                </Avatar>
                <span class="font-medium">{{ apprentice.name }}</span>
            </div>
        </TableCell>

        <TableCell>{{ apprentice.track }}</TableCell>
        <TableCell>{{ apprentice.year }}</TableCell>

        <TableCell>
            <AssignmentBadge :value="apprentice.coach" />
        </TableCell>

        <TableCell>
            <AssignmentBadge :value="apprentice.trainer" />
        </TableCell>
    </TableRow>
</template>
