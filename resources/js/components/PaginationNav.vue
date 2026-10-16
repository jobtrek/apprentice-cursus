<script setup lang="ts">
import { ChevronLeftIcon, ChevronRightIcon } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{ pageCount: number }>();

const page = defineModel<number>({ required: true });
</script>

<template>
    <nav
        v-if="pageCount > 1"
        class="flex items-center justify-between gap-2"
        aria-label="Pagination"
    >
        <Button
            variant="outline"
            size="sm"
            :disabled="page === 1"
            @click="page--"
        >
            <ChevronLeftIcon aria-hidden="true" />
            Précédent
        </Button>
        <span
            class="text-muted-foreground text-sm tabular-nums"
            aria-live="polite"
        >
            Page {{ page }} sur {{ pageCount }}
        </span>
        <Button
            variant="outline"
            size="sm"
            :disabled="page === pageCount"
            @click="page++"
        >
            Suivant
            <ChevronRightIcon aria-hidden="true" />
        </Button>
    </nav>
</template>
