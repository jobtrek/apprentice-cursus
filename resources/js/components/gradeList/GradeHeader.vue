<script setup lang="ts">
const props = defineProps<{ pageTitle: string }>()
</script>

<template>
    <header class="mb-4">
        <div class="flex flex-col gap-3">
            <h1 class="text-4xl font-bold">{{ pageTitle }}</h1>
            <slot/>
        </div>
    </header>
</template>
