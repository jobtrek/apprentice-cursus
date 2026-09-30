<script setup lang="ts" generic="T">
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

defineProps<{
    columns: { key: string; label: string; class?: string }[];
    data: T[];
    emptyMessage?: string;
}>();
</script>

<template>
    <div class="bg-card overflow-hidden rounded-xl border">
        <Table class="[&_td]:px-4 [&_th]:px-4">
            <TableHeader class="bg-muted/50">
                <TableRow class="hover:bg-transparent">
                    <TableHead
                        v-for="col in columns"
                        :key="col.key"
                        :class="col.class"
                        class="text-muted-foreground"
                    >
                        {{ col.label }}
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <template v-if="data.length > 0">
                    <slot v-for="item in data" name="row" :item="item" />
                </template>

                <TableRow v-else class="hover:bg-transparent">
                    <TableCell
                        :colspan="columns.length"
                        class="text-muted-foreground h-24 text-center"
                    >
                        {{ emptyMessage || 'Aucune donnée trouvée.' }}
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
