<script setup lang="ts" generic="T">
import {
    Table,
    TableBody,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";

defineProps<{
    columns: { key: string; label: string; class?: string }[];
    data: T[];
    emptyMessage?: string;
}>();
</script>

<template>
    <div class="overflow-hidden rounded-xl border bg-card">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead
                        v-for="col in columns"
                        :key="col.key"
                        :class="col.class"
                    >
                        {{ col.label }}
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <template v-if="data.length > 0">
                    <slot name="row" v-for="item in data" :item="item" />
                </template>

                <TableRow v-else>
                    <td
                        :colspan="columns.length"
                        class="h-24 text-center text-muted-foreground"
                    >
                        {{ emptyMessage || "Aucune donnée trouvée." }}
                    </td>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
