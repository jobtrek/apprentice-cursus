<script setup lang="ts">
const props = defineProps<{
    apprenticeName: string
    filiere: string
    cohort: string
    pageTitle: string
}>()
</script>

<template>
    <header class="mb-8">
        <p class="text-base text-muted-foreground">Mes apprentis · {{ apprenticeName }}</p>
        <p class="text-base text-muted-foreground mt-1">{{ filiere }} · {{ cohort }}</p>
        <div class="flex flex-row items-center gap-3 mt-4">
            <h1 class="text-4xl font-bold">{{ pageTitle }}</h1>
        </div>
    </header>
</template>
