<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = defineProps<{
    /** Libellé court affiché au-dessus de la valeur. */
    label: string;
    class?: string;
}>();
</script>

<template>
    <div :class="cn('flex min-w-0 flex-col gap-1', props.class)">
        <p
            class="text-muted-foreground text-xs font-medium tracking-wide uppercase"
        >
            {{ label }}
        </p>
        <div class="text-sm">
            <slot />
        </div>
    </div>
</template>
