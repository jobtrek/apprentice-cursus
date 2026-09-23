<script setup lang="ts">
import { MoonIcon, SunIcon } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, toggleAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');

const label = computed(() =>
    isDark.value ? 'Passer en thème clair' : 'Passer en thème sombre',
);
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        :aria-label="label"
        :title="label"
        :aria-pressed="isDark"
        class="text-muted-foreground"
        @click="toggleAppearance"
    >
        <SunIcon v-if="isDark" class="size-5" />
        <MoonIcon v-else class="size-5" />
    </Button>
</template>
