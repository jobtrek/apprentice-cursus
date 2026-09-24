<script setup lang="ts">
import { PlusIcon } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useAddGradeDialog } from '@/composables/useAddGradeDialog';

const { open } = useAddGradeDialog();
</script>

<template>
    <!--
        L'étiquette glisse à gauche au survol et au focus clavier ; les
        animations sont coupées quand l'utilisateur réduit les mouvements.
    -->
    <div
        class="group animate-in fade-in zoom-in-50 relative flex items-center duration-300 motion-reduce:animate-none"
    >
        <span
            aria-hidden="true"
            class="bg-foreground text-background pointer-events-none absolute top-1/2 right-full mr-3 translate-x-2 -translate-y-1/2 rounded-full px-3 py-1.5 text-sm font-medium whitespace-nowrap opacity-0 shadow-md transition-all duration-200 group-focus-within:translate-x-0 group-focus-within:opacity-100 group-hover:translate-x-0 group-hover:opacity-100 motion-reduce:transition-none"
        >
            Ajouter une note
        </span>

        <Button
            size="icon"
            class="size-14 rounded-full shadow-lg transition-transform duration-200 hover:scale-105 hover:shadow-xl active:scale-95 motion-reduce:transition-none motion-reduce:hover:scale-100"
            aria-label="Ajouter une note"
            data-test="add-grade-button"
            @click="open"
        >
            <PlusIcon
                class="size-6 transition-transform duration-300 group-hover:rotate-90 motion-reduce:transition-none"
                aria-hidden="true"
            />
        </Button>
    </div>
</template>
