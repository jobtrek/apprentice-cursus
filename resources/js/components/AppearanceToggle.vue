<script setup lang="ts">
import { MoonIcon, SunIcon } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, toggleAppearance } = useAppearance();

const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

const isDark = computed(
    () => isMounted.value && resolvedAppearance.value === 'dark',
);

const label = computed(() => {
    if (!isMounted.value) {
        return 'Changer de thème';
    }

    return isDark.value ? 'Passer en thème clair' : 'Passer en thème sombre';
});
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        :aria-label="label"
        :title="label"
        :aria-pressed="isMounted ? isDark : undefined"
        class="text-muted-foreground"
        @click="toggleAppearance"
    >
        <SunIcon class="hidden size-5 dark:block" aria-hidden="true" />
        <MoonIcon class="size-5 dark:hidden" aria-hidden="true" />
    </Button>
</template>
