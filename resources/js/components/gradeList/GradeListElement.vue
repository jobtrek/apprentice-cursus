<script setup lang="ts">

import {TableCell, TableRow} from "@/components/ui/table";
import type { Grade } from "@/types/grade";

defineProps<Grade>()
</script>

<template>
    <TableRow>
        <TableCell>{{ title }}</TableCell>
        <TableCell>{{ subject }}</TableCell>
        <TableCell>{{ value }}</TableCell>
        <TableCell>{{ semester }}</TableCell>
        <TableCell>{{ date }}</TableCell>
    </TableRow>
</template>
