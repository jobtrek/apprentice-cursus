<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        value: number;
        format?: (n: number) => string;
        /** Décalage du départ, en ms (cascade des tuiles). */
        delay?: number;
    }>(),
    {
        format: (n: number) => String(Math.round(n)),
        delay: 0,
    },
);

const DURATION = 900;

// Part de 0 côté serveur comme côté client : pas d'écart d'hydratation.
const shown = ref(0);
let frame = 0;

function animate(): void {
    cancelAnimationFrame(frame);

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        shown.value = props.value;

        return;
    }

    let start: number | null = null;
    const target = props.value;
    const tick = (now: number) => {
        start ??= now + props.delay;
        const t = Math.min(1, Math.max(0, (now - start) / DURATION));
        shown.value = target * (1 - (1 - t) ** 3);

        if (t < 1) {
            frame = requestAnimationFrame(tick);
        }
    };
    frame = requestAnimationFrame(tick);
}

onMounted(animate);
watch(() => props.value, animate);
onBeforeUnmount(() => cancelAnimationFrame(frame));
</script>

<template>
    <!-- Nombre qui défile au montage ; les lecteurs d'écran n'ont que la valeur finale. -->
    <span aria-hidden="true">{{ format(shown) }}</span>
    <span class="sr-only">{{ format(value) }}</span>
</template>
