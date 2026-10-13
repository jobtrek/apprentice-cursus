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
