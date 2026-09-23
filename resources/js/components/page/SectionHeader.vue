<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = defineProps<{
    /** Titre de la section, rendu en <h2> sous le <h1> de la page. */
    title: string;
    description?: string;
    class?: string;
}>();
</script>

<template>
    <div
        :class="
            cn('flex flex-wrap items-center justify-between gap-4', props.class)
        "
    >
        <div class="flex min-w-0 flex-col gap-1">
            <h2 class="text-lg font-semibold tracking-tight">{{ title }}</h2>
            <p v-if="description" class="text-muted-foreground text-sm">
                {{ description }}
            </p>
        </div>

        <div v-if="$slots.actions" class="flex items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
