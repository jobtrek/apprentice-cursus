<script setup lang="ts" generic="T">
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

const props = defineProps<{
    columns: {
        key: string;
        label: string;
        class?: string;
        /** Libellé lu par les lecteurs d'écran seulement (ex. « Actions »). */
        srOnly?: boolean;
    }[];
    data: T[];
    emptyMessage?: string;
    class?: string;
}>();
</script>

<template>
    <div :class="cn('bg-card overflow-hidden rounded-xl border', props.class)">
        <Table class="[&_td]:px-4 [&_th]:px-4">
            <TableHeader class="bg-muted/50">
                <TableRow class="hover:bg-transparent">
                    <TableHead
                        v-for="col in columns"
                        :key="col.key"
                        :class="col.class"
                        class="text-muted-foreground"
                    >
                        <span :class="{ 'sr-only': col.srOnly }">
                            {{ col.label }}
                        </span>
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
                        <slot name="empty">
                            {{ emptyMessage || 'Aucune donnée trouvée.' }}
                        </slot>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
