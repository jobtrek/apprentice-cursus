<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import MainNavbar from "@/layouts/MainNavbar.vue";
</script>

<template>
    <div class="bg-background min-h-svh">
        <MainNavbar />

        <main class="mx-auto max-w-sm px-6 py-10">
            <slot />
        </main>
    </div>
</template>
