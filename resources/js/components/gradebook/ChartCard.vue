<script setup lang="ts">
defineProps<{
    labelId: string;
    title: string;
    description: string;
}>();
</script>

<template>
    <section class="chart-card" :aria-labelledby="labelId">
        <div class="chart-card__head">
            <div>
                <h2 :id="labelId" class="chart-card__title">{{ title }}</h2>
                <p class="chart-card__desc">{{ description }}</p>
            </div>
            <slot name="aside" />
        </div>
        <slot />
    </section>
</template>
