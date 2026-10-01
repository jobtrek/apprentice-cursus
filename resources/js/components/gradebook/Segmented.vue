<script setup lang="ts" generic="T extends string | number">
defineProps<{
    options: { value: T; label: string }[];
    /** Nom accessible du groupe. */
    label: string;
}>();

const model = defineModel<T>({ required: true });
</script>

<template>
    <!-- Contrôle segmenté (.seg) : un seul bouton enfoncé. -->
    <div class="seg" role="group" :aria-label="label">
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            :aria-pressed="option.value === model"
            @click="model = option.value"
        >
            {{ option.label }}
        </button>
    </div>
</template>
