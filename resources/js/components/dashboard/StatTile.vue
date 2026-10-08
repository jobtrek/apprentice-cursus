<script setup lang="ts">
import type { Component } from 'vue';
import IconTile from '@/components/IconTile.vue';
import { Card, CardContent } from '@/components/ui/card';
import { TONES, type Tone } from '@/lib/apprentice';

withDefaults(
    defineProps<{
        label: string;
        value: string;
        /** Précision sous la valeur (tendance, période…). */
        hint?: string;
        icon: Component;
        /** Teinte de l'icône. */
        tone?: Tone;
    }>(),
    { hint: undefined, tone: 'primary' },
);
</script>

<template>
    <Card class="gap-0 py-0 shadow-xs">
        <CardContent class="flex flex-col gap-4 p-5">
            <div class="flex items-start justify-between gap-2">
                <IconTile :class="TONES[tone].text">
                    <component :is="icon" />
                </IconTile>
                <!-- Pastille en haut à droite : variation, statut… -->
                <slot name="badge" />
            </div>
            <div class="flex flex-col gap-1">
                <p class="text-muted-foreground text-sm font-medium">
                    {{ label }}
                </p>
                <p class="text-3xl font-semibold tracking-tight tabular-nums">
                    {{ value }}
                </p>
                <p
                    v-if="hint || $slots.hint"
                    class="text-muted-foreground flex items-center gap-1 text-xs"
                >
                    <slot name="hint">{{ hint }}</slot>
                </p>
            </div>
        </CardContent>
    </Card>
</template>
