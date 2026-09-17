<script setup lang="ts">
defineProps<{
    title: string;
    caption?: string;
}>();
</script>

<template>
    <section class="bg-card rounded-lg border p-4">
        <header>
            <h2 class="text-sm font-medium">{{ title }}</h2>
            <p v-if="caption" class="text-muted-foreground mt-1 text-xs">
                {{ caption }}
            </p>
        </header>

        <div class="mt-4">
            <slot />
        </div>

        <!--
            Jumelle tabulaire : toute valeur lisible sur le graphique reste
            atteignable sans survol ni distinction de couleur.
        -->
        <details class="mt-3">
            <summary
                class="text-muted-foreground hover:text-foreground cursor-pointer text-xs"
            >
                Voir les valeurs
            </summary>

            <div class="mt-2 overflow-x-auto">
                <slot name="table" />
            </div>
        </details>
    </section>
</template>
