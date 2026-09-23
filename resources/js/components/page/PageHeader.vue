<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { LinkComponentBaseProps } from '@inertiajs/core';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { cn } from '@/lib/utils';

export type PageBreadcrumb = {
    label: string;
    href: LinkComponentBaseProps['href'];
};

const props = defineProps<{
    /** Titre de la page, rendu en <h1>. Une seule fois par page. */
    title: string;
    /** Phrase d'accroche facultative sous le titre. */
    description?: string;
    /**
     * Pages parentes affichées au-dessus du titre. La page courante n'y
     * figure pas : c'est le titre qui la nomme.
     */
    breadcrumbs?: PageBreadcrumb[];
    class?: string;
}>();
</script>

<template>
    <div :class="cn('flex flex-col gap-2', props.class)">
        <Breadcrumb v-if="breadcrumbs?.length">
            <BreadcrumbList>
                <template
                    v-for="(crumb, index) in breadcrumbs"
                    :key="crumb.label"
                >
                    <BreadcrumbSeparator v-if="index > 0" />
                    <BreadcrumbItem>
                        <BreadcrumbLink as-child>
                            <Link :href="crumb.href">{{ crumb.label }}</Link>
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                </template>
            </BreadcrumbList>
        </Breadcrumb>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 flex-col gap-1">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ title }}
                </h1>
                <p
                    v-if="description || $slots.description"
                    class="text-muted-foreground text-sm"
                >
                    <slot name="description">{{ description }}</slot>
                </p>

                <!-- Contenu additionnel sous le titre (méta, badges…). -->
                <slot />
            </div>

            <div v-if="$slots.actions" class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
