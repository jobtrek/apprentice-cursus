<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = defineProps<{
    /** Titre de la page, rendu en <h1>. Une seule fois par page. */
    title: string;
    /** Phrase d'accroche facultative sous le titre. */
    description?: string;
    class?: string;
}>();
</script>

<template>
    <div
        :class="
            cn('flex flex-wrap items-start justify-between gap-4', props.class)
        "
    >
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
            <p
                v-if="description || $slots.description"
                class="text-muted-foreground text-sm"
            >
                <slot name="description">{{ description }}</slot>
            </p>

            <!-- Contenu additionnel sous le titre (fil d'Ariane, méta…). -->
            <slot />
        </div>

        <div v-if="$slots.actions" class="flex items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
