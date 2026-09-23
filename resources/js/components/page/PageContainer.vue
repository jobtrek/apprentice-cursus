<script setup lang="ts">
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const pageContainerVariants = cva('mx-auto flex w-full flex-col', {
    variants: {
        /**
         * Largeur maximale du contenu. Toute page de l'application doit se
         * ranger dans l'un de ces paliers plutôt que de définir sa propre
         * largeur : c'est ce qui garantit l'alignement d'un écran à l'autre.
         */
        size: {
            sm: 'max-w-2xl', // Formulaires
            md: 'max-w-4xl', // Pages de contenu standard
            lg: 'max-w-7xl', // Tableaux de bord et tableaux larges
            full: 'max-w-none',
        },
        /** Rythme vertical entre les blocs de premier niveau de la page. */
        gap: {
            none: 'gap-0',
            md: 'gap-6',
            lg: 'gap-8',
        },
    },
    defaultVariants: {
        size: 'md',
        gap: 'md',
    },
});

type PageContainerVariants = VariantProps<typeof pageContainerVariants>;

const props = defineProps<{
    size?: PageContainerVariants['size'];
    gap?: PageContainerVariants['gap'];
    class?: string;
}>();
</script>

<template>
    <div
        :class="
            cn(
                pageContainerVariants({ size: props.size, gap: props.gap }),
                props.class,
            )
        "
    >
        <slot />
    </div>
</template>
