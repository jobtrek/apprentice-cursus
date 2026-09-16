<script setup lang="ts">
import { MoonIcon, SunIcon } from '@lucide/vue';
import { computed } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, toggleAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');

const label = computed(() =>
    isDark.value ? 'Passer en thème clair' : 'Passer en thème sombre',
);
</script>

<template>
    <button
        type="button"
        :aria-label="label"
        :title="label"
        :aria-pressed="isDark"
        class="text-muted-foreground hover:bg-accent hover:text-foreground focus-visible:ring-ring/50 inline-flex size-9 items-center justify-center rounded-md transition-colors focus-visible:ring-[3px] focus-visible:outline-none"
        @click="toggleAppearance"
    >
        <SunIcon v-if="isDark" class="size-5" />
        <MoonIcon v-else class="size-5" />
    </button>
</template>
