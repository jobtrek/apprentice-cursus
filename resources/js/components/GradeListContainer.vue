<script setup lang="ts">
import {Table, TableHeader, TableHead, TableRow, TableBody} from "@/components/ui/table";
import {Card} from "@/components/ui/card";
import type {HTMLAttributes} from "vue";
import { cn } from "@/lib/utils";
const props = defineProps<{ class?: HTMLAttributes["class"] }>()
</script>

<template>
    <div :class="cn('relative w-full overflow-auto', props.class)">
        <Table>
            <TableHeader>
                <tr class="text-base font-bold">
                        <TableHead>Module</TableHead>
                        <TableHead>Matière</TableHead>
                        <TableHead>Note</TableHead>
                        <TableHead>Semestre</TableHead>
                        <TableHead>Date</TableHead>
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
