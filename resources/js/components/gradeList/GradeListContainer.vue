<script setup lang="ts">
import {Table, TableHeader, TableHead, TableRow, TableBody} from "@/components/ui/table";
import {Card} from "@/components/ui/card";
import type {HTMLAttributes} from "vue";
import { cn } from "@/lib/utils";
const props = defineProps<{ class?: HTMLAttributes["class"],
    columns: string[],
    tableTitle: string
}>()

</script>

<template>
    <h2 class="px-4 pt-2  bg-white font-bold">{{ tableTitle }}</h2>
    <div :class="cn('relative w-full overflow-auto', props.class)">
        <Table>
            <TableHeader>
                <tr class="text-base font-bold">
                        <TableHead v-for="column in columns">{{ column }}</TableHead>
                </tr>
            </TableHeader>
            <TableBody>
                    <slot />
            </TableBody>
        </Table>
    </div>
</template>

<style scoped>

</style>
