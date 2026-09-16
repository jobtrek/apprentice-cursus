<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { TableCell, TableRow } from "@/components/ui/table";
import AssignmentBadge from "./AssignmentBadge.vue";
import type { Apprentice } from "@/composables/useApprentices";

defineProps<{
    apprentice: Apprentice;
}>();

defineEmits<{
    select: [apprentice: Apprentice];
}>();
</script>

<template>
    <TableRow
        class="cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        tabindex="0"
        @click="$emit('select', apprentice)"
        @keydown.enter.prevent="$emit('select', apprentice)"
        @keydown.space.prevent="$emit('select', apprentice)"
    >
        <TableCell class="py-4">
            <div class="flex items-center gap-3">
                <Avatar>
                    <AvatarImage :src="apprentice.avatarUrl ?? ''" />
                    <AvatarFallback>{{
                        apprentice.name.charAt(0)
                    }}</AvatarFallback>
                </Avatar>
                <span class="font-medium text-card-foreground">{{
                    apprentice.name
                }}</span>
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
