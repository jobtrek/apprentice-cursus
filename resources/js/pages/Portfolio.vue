<script setup lang="ts">
import { Head, Link, useRemember } from '@inertiajs/vue3';
import {
    EyeIcon,
    FolderOpenIcon,
    GitCommitVerticalIcon,
    ListIcon,
    PlusIcon,
} from '@lucide/vue';
import { ref } from 'vue';
import { formatPeriod } from '@/composables/usePortfolio';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import portfolio from '@/routes/portfolio';
import { PageContainer, PageHeader } from '@/components/page';
import PortfolioStats from '@/components/portfolio/PortfolioStats.vue';
import ProjectDialog from '@/components/portfolio/ProjectDialog.vue';
import ProjectTimeline from '@/components/portfolio/ProjectTimeline.vue';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useProjectDialog } from '@/composables/useProjectDialog';
import type { PortfolioProject, Skill } from '@/types/portfolio';

/**
 * L'ordre des projets est celui du serveur. Le glisser-déposer reviendra quand
 * la table `projects` aura une colonne `position` : sans elle, l'ordre choisi
 * était perdu au rechargement.
 */
defineProps<{
    projects: PortfolioProject[];
    skills: Skill[];
}>();

const { openCreate: openAddProject, openEdit: openEditProject } =
    useProjectDialog();

/** Liste des projets, ou chronologie triée par date de début. */
type PortfolioView = 'list' | 'timeline';
const view = useRemember(ref<PortfolioView>('list'), 'Portfolio:view');

const selectView = (value: unknown): void => {
    // Un ToggleGroup « single » renvoie une valeur vide si on reclique l'actif.
    if (value === 'list' || value === 'timeline') {
        view.value = value;
    }
};
</script>

<template>
    <Head title="Portfolio" />

    <PageContainer>
        <PageHeader
            title="Portfolio"
            description="Vos projets de formation, réunis dans un aperçu exportable en PDF."
        >
            <template #actions>
                <Button as-child variant="outline">
                    <Link :href="portfolio.preview()">
                        <EyeIcon aria-hidden="true" />
                        Aperçu
                    </Link>
                </Button>
                <Button data-test="new-project-button" @click="openAddProject">
                    <PlusIcon aria-hidden="true" />
                    Nouveau projet
                </Button>
            </template>
        </PageHeader>

        <section
            v-if="projects.length > 0"
            class="flex flex-col gap-3"
            aria-label="Projets"
        >
            <PortfolioStats :projects="projects" />

            <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
                <p class="text-muted-foreground text-sm">
                    {{
                        view === 'list'
                            ? 'Cliquez sur un projet pour le modifier.'
                            : 'Du plus récent au plus ancien.'
                    }}
                </p>
                <ToggleGroup
                    :model-value="view"
                    type="single"
                    variant="outline"
                    size="sm"
                    aria-label="Affichage des projets"
                    @update:model-value="selectView"
                >
                    <ToggleGroupItem value="list" class="px-3">
                        <ListIcon aria-hidden="true" />
                        Liste
                    </ToggleGroupItem>
                    <ToggleGroupItem value="timeline" class="px-3">
                        <GitCommitVerticalIcon aria-hidden="true" />
                        Chronologie
                    </ToggleGroupItem>
                </ToggleGroup>
            </div>

            <ProjectTimeline
                v-if="view === 'timeline'"
                :projects="projects"
                class="pt-2"
                @edit="openEditProject"
            />

            <Card v-else class="gap-0 overflow-hidden py-0 shadow-xs">
                <ul>
                    <!-- Le titre couvre toute la ligne (lien étiré) : un clic l'ouvre. -->
                    <li
                        v-for="project in projects"
                        :key="project.id"
                        class="hover:bg-muted/30 focus-within:bg-muted/30 relative flex items-start gap-3 border-b p-4 transition-colors last:border-b-0 sm:gap-4"
                    >
                        <!-- Première capture du projet, ou une vignette neutre. -->
                        <div
                            class="bg-muted text-muted-foreground hidden h-20 w-32 flex-none items-center justify-center overflow-hidden rounded-lg border sm:flex"
                        >
                            <img
                                v-if="project.screenshots.length > 0"
                                :src="project.screenshots[0].url"
                                alt=""
                                class="size-full object-cover"
                            />
                            <FolderOpenIcon
                                v-else
                                class="size-5"
                                aria-hidden="true"
                            />
                        </div>

                        <div class="flex min-w-0 flex-1 flex-col gap-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="truncate text-left font-medium outline-none after:absolute after:inset-0 hover:underline focus-visible:underline"
                                    :aria-label="`Modifier ${project.title}`"
                                    @click="openEditProject(project)"
                                >
                                    {{ project.title }}
                                </button>
                                <Badge
                                    v-if="!project.date_end"
                                    class="bg-success/15 text-success border-transparent"
                                >
                                    En cours
                                </Badge>
                            </div>
                            <p class="text-muted-foreground text-sm">
                                {{
                                    [
                                        project.organization,
                                        formatPeriod(
                                            project.date_start,
                                            project.date_end,
                                        ),
                                        project.responsibilities,
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')
                                }}
                            </p>
                            <p
                                class="text-muted-foreground line-clamp-2 text-sm"
                            >
                                {{ project.description }}
                            </p>
                            <div
                                v-if="project.technologies.length > 0"
                                class="flex flex-wrap gap-1 pt-1"
                            >
                                <Badge
                                    v-for="technology in project.technologies"
                                    :key="technology"
                                    variant="outline"
                                >
                                    {{ technology }}
                                </Badge>
                            </div>
                        </div>
                    </li>
                </ul>
            </Card>
        </section>

        <Empty v-else class="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <FolderOpenIcon />
                </EmptyMedia>
                <EmptyTitle>Aucun projet pour l'instant</EmptyTitle>
                <EmptyDescription>
                    Ajoutez votre premier projet pour commencer à constituer
                    votre portfolio de formation.
                </EmptyDescription>
            </EmptyHeader>
            <EmptyContent>
                <Button @click="openAddProject">
                    <PlusIcon aria-hidden="true" />
                    Nouveau projet
                </Button>
            </EmptyContent>
        </Empty>

        <ProjectDialog :skills="skills" />
    </PageContainer>
</template>
