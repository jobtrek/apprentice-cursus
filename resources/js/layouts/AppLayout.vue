<script setup lang="ts">
import { usePage } from "@inertiajs/vue3";
import MainNavbar from "@/layouts/MainNavbar.vue";
import MainNavbar from '@/layouts/MainNavbar.vue';
</script>

<template>
    <div class="bg-background flex min-h-svh flex-col">
        <MainNavbar />

        <main class="flex flex-1 flex-col items-center justify-center">
            <slot />
        </main>
    </div>
</template>
