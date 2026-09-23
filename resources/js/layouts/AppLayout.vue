<script setup lang="ts">
import MainNavbar from '@/layouts/MainNavbar.vue';
</script>

<template>
    <div class="bg-background flex min-h-svh flex-col">
        <!-- Permet aux utilisateurs du clavier de passer la navigation. -->
        <a
            href="#main-content"
            class="bg-background focus-visible:ring-ring/50 sr-only z-50 rounded-md px-4 py-2 text-sm font-medium focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus-visible:ring-[3px] focus-visible:outline-none"
        >
            Aller au contenu
        </a>

        <MainNavbar />

        <!--
            Le contenu est aligné en haut : un centrage vertical ferait sauter
            la position du titre d'une page à l'autre selon sa hauteur. La
            gouttière horizontale vit ici, la largeur dans <PageContainer>.
        -->
        <main
            id="main-content"
            tabindex="-1"
            class="flex flex-1 flex-col px-4 py-8 focus:outline-none sm:px-6"
        >
            <slot />
        </main>
    </div>
</template>
