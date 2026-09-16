<script setup lang="ts">
import {
    Table,
    TableBody,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import ApprentiRow from "./ApprenticeRow.vue";
import type { Apprentice } from "@/composables/useApprentices";

defineProps<{
    apprentices: Apprentice[];
}>();

defineEmits<{
    select: [apprentice: Apprentice];
}>();
</script>

<template>
    <div class="rounded-xl border bg-card overflow-hidden">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Apprenti·e</TableHead>
                    <TableHead>Filière</TableHead>
                    <TableHead>Année</TableHead>
                    <TableHead>Coach</TableHead>
                    <TableHead>Formateur</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <ApprentiRow
                    v-for="apprentice in apprentices"
                    :key="apprentice.id"
                    :apprentice="apprentice"
                    @select="$emit('select', $event)"
                />
            </TableBody>
        </Table>
    </div>
</template>
