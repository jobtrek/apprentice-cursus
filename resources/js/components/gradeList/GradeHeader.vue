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

        <div class="flex flex-col gap-3 mt-4">
            <h1 class="text-4xl font-bold">{{ pageTitle }}</h1>
            <h2 class="text-base text-muted-foreground">
                Mes apprentis · {{ cohort }} · {{ filiere }} · {{ apprenticeName  }}</h2>
        </div>
    </header>
</template>
