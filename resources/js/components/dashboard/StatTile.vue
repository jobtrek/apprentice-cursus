<script setup lang="ts">
defineProps<{
    label: string;
    value: string;
    hint?: string;
}>();
</script>

<template>
    <div class="bg-card rounded-lg border p-4">
        <p class="text-muted-foreground text-sm">{{ label }}</p>

        <p class="mt-2 text-3xl leading-none font-semibold">{{ value }}</p>

        <p v-if="hint" class="text-muted-foreground mt-2 text-xs">
            {{ hint }}
        </p>
    </div>
</template>
