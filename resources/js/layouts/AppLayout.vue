<script setup lang="ts">
import MainNavbar from "@/layouts/MainNavbar.vue";
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <MainNavbar />

        <main class="flex flex-1 flex-col items-center justify-center">
            <slot />
        </main>
    </div>
</template>
