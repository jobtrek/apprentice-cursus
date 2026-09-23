<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { TableCell, TableRow } from '@/components/ui/table';
import type { Grade } from '@/types/grade';
import type { RouteDefinition } from '@/wayfinder';

const props = defineProps<
    Grade & {
        /** Page de détail ouverte au clic sur la ligne. */
        href: RouteDefinition<'get'>;
    }
>();

const openDetail = () => router.visit(props.href);
</script>

<template>
    <TableRow
        class="focus-visible:bg-muted/50 cursor-pointer focus-visible:outline-none"
        tabindex="0"
        @click="openDetail"
        @keydown.enter.prevent="openDetail"
        @keydown.space.prevent="openDetail"
    >
        <TableCell class="font-medium">{{ title }}</TableCell>
        <TableCell>{{ subject }}</TableCell>
        <TableCell class="font-semibold tabular-nums">
            {{ value.toFixed(1) }}
        </TableCell>
        <TableCell>{{ semester }}</TableCell>
        <TableCell class="tabular-nums">{{ date }}</TableCell>
    </TableRow>
</template>
