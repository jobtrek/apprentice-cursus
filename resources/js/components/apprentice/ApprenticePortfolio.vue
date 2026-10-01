<script setup lang="ts">
import { ExternalLinkIcon, FolderOpenIcon } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { formatPeriod, skillNames } from '@/composables/usePortfolio';
import type { SupervisedPortfolio } from '@/types/portfolio';

defineProps<{
    portfolio: SupervisedPortfolio;
}>();
</script>

<template>
    <!-- Lecture seule : le coach et le formateur ne modifient jamais le portfolio. -->
    <div v-if="portfolio.projects.length > 0" class="grid gap-4 lg:grid-cols-2">
        <Card
            v-for="project in portfolio.projects"
            :key="project.id"
            class="gap-4"
        >
            <CardHeader>
                <CardTitle>{{ project.title }}</CardTitle>
                <CardDescription>
                    <template v-if="project.organization">
                        {{ project.organization }} ·
                    </template>
                    {{ formatPeriod(project.date_start, project.date_end) }}
                    <template v-if="project.responsibilities">
                        · {{ project.responsibilities }}
                    </template>
                </CardDescription>
            </CardHeader>

            <CardContent class="flex flex-col gap-4">
                <p class="text-sm/relaxed whitespace-pre-line">
                    {{ project.description }}
                </p>

                <div
                    v-if="project.screenshots.length > 0"
                    class="flex gap-2 overflow-x-auto"
                >
                    <a
                        v-for="(screenshot, index) in project.screenshots"
                        :key="screenshot.id"
                        :href="screenshot.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="shrink-0"
                    >
                        <img
                            :src="screenshot.url"
                            :alt="`${project.title} — capture ${index + 1}`"
                            loading="lazy"
                            class="bg-muted h-20 w-32 rounded-md object-cover"
                        />
                    </a>
                </div>

                <div
                    v-if="project.technologies.length > 0"
                    class="flex flex-wrap gap-1.5"
                >
                    <Badge
                        v-for="technology in project.technologies"
                        :key="technology"
                        variant="outline"
                    >
                        {{ technology }}
                    </Badge>
                </div>

                <div
                    v-if="skillNames(project, portfolio.skills).length > 0"
                    class="flex flex-wrap gap-1.5"
                >
                    <Badge
                        v-for="name in skillNames(project, portfolio.skills)"
                        :key="name"
                        variant="secondary"
                    >
                        {{ name }}
                    </Badge>
                </div>

                <div
                    v-if="project.demo_path || project.repository_url"
                    class="flex flex-wrap gap-4 text-sm"
                >
                    <a
                        v-if="project.demo_path"
                        :href="project.demo_path"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-1.5 underline-offset-4 hover:underline"
                    >
                        <ExternalLinkIcon class="size-3.5" aria-hidden="true" />
                        Démonstration
                    </a>
                    <a
                        v-if="project.repository_url"
                        :href="project.repository_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-1.5 underline-offset-4 hover:underline"
                    >
                        <ExternalLinkIcon class="size-3.5" aria-hidden="true" />
                        Code source
                    </a>
                </div>
            </CardContent>
        </Card>
    </div>

    <Empty v-else class="border">
        <EmptyHeader>
            <EmptyMedia variant="icon">
                <FolderOpenIcon />
            </EmptyMedia>
            <EmptyTitle>Aucun projet pour l'instant</EmptyTitle>
            <EmptyDescription>
                Les projets ajoutés par l'apprenti·e à son portfolio
                apparaîtront ici.
            </EmptyDescription>
        </EmptyHeader>
    </Empty>
</template>
