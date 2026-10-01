<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, shallowRef } from 'vue';

/** Infobulle d'un graphique : valeur en gras, puis une ou deux lignes. */
export interface ChartTip {
    x: number;
    y: number;
    value: string;
    label?: string;
    detail?: string;
}

defineSlots<{
    default(props: {
        width: number;
        setTip: (tip: ChartTip | null) => void;
    }): unknown;
}>();

const body = ref<HTMLElement | null>(null);
// 0 côté serveur : le SVG n'est dessiné qu'une fois la largeur connue.
const width = ref(0);
const tip = shallowRef<ChartTip | null>(null);
// Garde le contenu pendant le fondu de sortie.
const last = shallowRef<ChartTip | null>(null);

function setTip(next: ChartTip | null): void {
    tip.value = next;

    if (next) {
        last.value = next;
    }
}

let observer: ResizeObserver | null = null;

onMounted(() => {
    if (!body.value) {
        return;
    }

    width.value = body.value.clientWidth;
    // Redessiné au redimensionnement : le texte garde sa vraie taille en pixels.
    observer = new ResizeObserver(() => {
        width.value = body.value?.clientWidth ?? 0;
    });
    observer.observe(body.value);
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <div ref="body" class="chart-card__body">
        <slot v-if="width > 0" :width="width" :set-tip="setTip" />
        <div
            :class="tip ? 'chart-tip is-on' : 'chart-tip'"
            role="status"
            :style="
                last ? { left: `${last.x}px`, top: `${last.y}px` } : undefined
            "
        >
            <template v-if="last">
                <b>{{ last.value }}</b>
                <template v-if="last.label">{{ last.label }}</template>
                <template v-if="last.detail">
                    <br />
                    <span>{{ last.detail }}</span>
                </template>
            </template>
        </div>
    </div>
</template>
