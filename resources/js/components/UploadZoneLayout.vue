<script setup lang="ts">
import { UploadIcon } from '@lucide/vue';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        as?: string;
        disabled?: boolean;
        invalid?: boolean;
        hint?: string;
        fileName?: string | null;
    }>(),
    { as: 'div' },
);
</script>

<template>
    <component
        :is="as"
        :class="
            cn(
                'flex flex-col items-center justify-center gap-1 rounded-md border border-dashed p-8 text-center transition-colors',
                disabled
                    ? 'cursor-not-allowed opacity-50'
                    : 'hover:bg-muted/50',
                invalid && 'border-destructive',
            )
        "
    >
        <slot name="icon">
            <UploadIcon class="text-muted-foreground size-5" />
        </slot>
        <span class="text-sm"><slot /></span>
        <span v-if="hint" class="text-muted-foreground text-xs">{{
            hint
        }}</span>
        <span v-if="fileName" class="text-foreground text-xs">{{
            fileName
        }}</span>
        <slot name="extra" />
    </component>
</template>
