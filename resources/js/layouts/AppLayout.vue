<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import MainNavbar from "@/layouts/MainNavbar.vue";
</script>

<template>
    <div class="bg-background min-h-svh flex flex-col">
        <MainNavbar />

        <main class="flex flex-1 flex-col justify-center items-center">
            <slot />
        </main>
    </div>
</template>
