<script setup lang="ts">
import MainNavbar from '@/layouts/MainNavbar.vue';
</script>

<template>
    <div class="bg-background flex min-h-svh flex-col">
        <MainNavbar />

        <!--
            Le contenu est aligné en haut : un centrage vertical ferait sauter
            la position du titre d'une page à l'autre selon sa hauteur. La
            gouttière horizontale vit ici, la largeur dans <PageContainer>.
        -->
        <main class="flex flex-1 flex-col px-4 py-8 sm:px-6">
            <slot />
        </main>
    </div>
</template>
