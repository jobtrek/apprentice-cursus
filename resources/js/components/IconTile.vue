<script setup lang="ts">
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

/**
 * Pastille carrée qui donne aux icônes une surface commune. Adaptée de l'Icon
 * Tile de reui.io (https://reui.io/components/icon-tile).
 *
 * `soft` dérive fond et bordures de `currentColor` : une seule classe de texte
 * (ex. `text-destructive`) reteinte toute la pastille. Le cadre intérieur est
 * peint par `::after` ; `isolate` le garde sous l'icône.
 */
const iconTileVariants = cva(
    [
        'relative inline-flex shrink-0 items-center justify-center align-middle',
        'size-(--icon-tile-size) rounded-(--icon-tile-radius) [--icon-tile-radius:var(--radius-lg)]',
        '[&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=size-])]:size-(--icon-tile-icon-size)',
    ],
    {
        variants: {
            variant: {
                /** Surface bordée, discrète. */
                outline: 'border-border bg-background dark:bg-input/30 border',
                /** Double cadre teinté d'après la couleur du texte. */
                soft: [
                    'isolate bg-current/10 p-(--icon-tile-inset)',
                    'after:absolute after:inset-(--icon-tile-inset) after:-z-10',
                    'after:rounded-[calc(var(--icon-tile-radius)-var(--icon-tile-inset))]',
                    'after:border after:border-current/20 after:bg-current/5',
                ],
                /** Anneau neutre autour d'une carte en retrait. */
                frame: [
                    'border-border bg-muted/50 isolate border p-(--icon-tile-inset)',
                    'after:absolute after:inset-(--icon-tile-inset) after:-z-10',
                    'after:rounded-[calc(var(--icon-tile-radius)-var(--icon-tile-inset))]',
                    'after:border-border after:bg-card after:border after:shadow-xs',
                ],
            },
            size: {
                sm: '[--icon-tile-icon-size:--spacing(4)] [--icon-tile-inset:--spacing(0.5)] [--icon-tile-size:--spacing(8)]',
                default:
                    '[--icon-tile-icon-size:--spacing(4.5)] [--icon-tile-inset:--spacing(0.75)] [--icon-tile-size:--spacing(10)]',
                lg: '[--icon-tile-icon-size:--spacing(5.5)] [--icon-tile-inset:--spacing(0.75)] [--icon-tile-size:--spacing(12)]',
            },
        },
        defaultVariants: { variant: 'soft', size: 'default' },
    },
);

type IconTileVariants = VariantProps<typeof iconTileVariants>;

const props = defineProps<{
    variant?: IconTileVariants['variant'];
    size?: IconTileVariants['size'];
    class?: string;
}>();
</script>

<template>
    <span
        data-slot="icon-tile"
        aria-hidden="true"
        :class="
            cn(
                iconTileVariants({ variant, size }),
                (variant ?? 'soft') === 'soft' && 'text-primary',
                props.class,
            )
        "
    >
        <slot />
    </span>
</template>
