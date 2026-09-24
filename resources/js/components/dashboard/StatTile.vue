<script setup lang="ts">
import type { Component } from 'vue';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
} from '@/components/ui/card';

defineProps<{
    label: string;
    value: string;
    /** Précision sous la valeur (tendance, période…). */
    hint?: string;
    icon: Component;
}>();
</script>

<template>
    <Card class="gap-2">
        <CardHeader>
            <CardDescription>{{ label }}</CardDescription>
            <CardAction>
                <component
                    :is="icon"
                    class="text-muted-foreground size-4"
                    aria-hidden="true"
                />
            </CardAction>
        </CardHeader>
        <CardContent class="flex flex-col gap-1">
            <p class="text-3xl font-semibold tabular-nums">{{ value }}</p>
            <p
                v-if="hint || $slots.hint"
                class="text-muted-foreground flex items-center gap-1 text-xs"
            >
                <slot name="hint">{{ hint }}</slot>
            </p>
        </CardContent>
    </Card>
</template>
