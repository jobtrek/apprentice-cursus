<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(
    defineProps<{
        value: number;
        max?: number;
        size?: number;
        strokeWidth?: number;
        color?: string;
        trackColor?: string;
    }>(),
    {
        max: 6,
        size: 120,
        strokeWidth: 10,
        color: "#22c55e",
        trackColor: "rgba(255, 255, 255, 0.08)",
    },
);

const SWEEP = 270;
const GAP_ROTATE = 135;

const radius = computed(() => (props.size - props.strokeWidth) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const trackLength = computed(() => (SWEEP / 360) * circumference.value);

const progress = computed(() => {
    const ratio = props.max > 0 ? props.value / props.max : 0;
    return Math.min(Math.max(ratio, 0), 1);
});
const progressLength = computed(() => progress.value * trackLength.value);
</script>

<template>
    <div
        class="relative inline-flex"
        :style="{ width: `${size}px`, height: `${size}px` }"
    >
        <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`">
            <circle
                :cx="size / 2"
                :cy="size / 2"
                :r="radius"
                fill="none"
                :stroke="trackColor"
                :stroke-width="strokeWidth"
                stroke-linecap="round"
                :stroke-dasharray="`${trackLength} ${circumference}`"
                :transform="`rotate(${GAP_ROTATE} ${size / 2} ${size / 2})`"
            />
            <circle
                :cx="size / 2"
                :cy="size / 2"
                :r="radius"
                fill="none"
                :stroke="color"
                :stroke-width="strokeWidth"
                stroke-linecap="round"
                :stroke-dasharray="`${progressLength} ${circumference}`"
                :transform="`rotate(${GAP_ROTATE} ${size / 2} ${size / 2})`"
            />
        </svg>
        <div
            v-if="$slots.default"
            class="absolute inset-0 flex items-center justify-center"
        >
            <slot />
        </div>
    </div>
</template>
