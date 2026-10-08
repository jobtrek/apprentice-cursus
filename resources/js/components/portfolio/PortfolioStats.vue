<script setup lang="ts">
import { CodeIcon, FolderKanbanIcon, LoaderIcon } from '@lucide/vue';
import { computed } from 'vue';
import IconTile from '@/components/IconTile.vue';
import type { PortfolioProject } from '@/types/portfolio';

const props = defineProps<{ projects: PortfolioProject[] }>();

const stats = computed(() => {
    const technologies = new Set(
        props.projects.flatMap((project) =>
            project.technologies.map((technology) => technology.toLowerCase()),
        ),
    );

    return [
        {
            label: 'Projets',
            value: props.projects.length,
            icon: FolderKanbanIcon,
        },
        {
            label: 'En cours',
            value: props.projects.filter((project) => !project.date_end).length,
            icon: LoaderIcon,
        },
        {
            label: 'Technologies',
            value: technologies.size,
            icon: CodeIcon,
        },
    ];
});
</script>

<template>
    <!--
        Cadre à panneaux, d'après le Frame de reui.io : un fond atténué qui
        entoure des cartes en retrait.
    -->
    <dl
        class="bg-muted/50 grid grid-cols-3 gap-1 rounded-xl border p-1"
        aria-label="Chiffres du portfolio"
    >
        <div
            v-for="stat in stats"
            :key="stat.label"
            class="bg-card flex items-center gap-3 rounded-lg border p-3 shadow-xs"
        >
            <IconTile size="sm" class="hidden sm:inline-flex">
                <component :is="stat.icon" />
            </IconTile>
            <div class="flex min-w-0 flex-col">
                <dt class="text-muted-foreground truncate text-xs">
                    {{ stat.label }}
                </dt>
                <dd class="text-lg leading-tight font-semibold tabular-nums">
                    {{ stat.value }}
                </dd>
            </div>
        </div>
    </dl>
</template>
