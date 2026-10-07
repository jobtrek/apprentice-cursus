<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    EyeIcon,
    FolderOpenIcon,
    GripVerticalIcon,
    PencilIcon,
    PlusIcon,
} from '@lucide/vue';
import { ref, watch } from 'vue';
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
import ProjectDialog from '@/components/portfolio/ProjectDialog.vue';
import { useProjectDialog } from '@/composables/useProjectDialog';
import type { PortfolioProject, Skill } from '@/types/portfolio';

const props = defineProps<{
    projects: PortfolioProject[];
    skills: Skill[];
}>();

const { openCreate: openAddProject, openEdit: openEditProject } =
    useProjectDialog();

/**
 * Copie locale pour le glisser-déposer. L'ordre n'est pas encore enregistré :
 * la table `projects` n'a pas de colonne `position`. Resynchronisée quand le
 * serveur renvoie la liste (après un ajout, une modification, une suppression).
 */
const projects = ref<PortfolioProject[]>([...props.projects]);

watch(
    () => props.projects,
    (fresh) => {
        projects.value = [...fresh];
    },
);

/** Déplace un projet dans la liste, en bornant la position d'arrivée. */
function moveProject(from: number, to: number): void {
    if (to < 0 || to >= projects.value.length || from === to) {
        return;
    }

    const reordered = [...projects.value];
    const [moved] = reordered.splice(from, 1);
    reordered.splice(to, 0, moved);
    projects.value = reordered;
}

const draggedIndex = ref<number | null>(null);
const dropTargetIndex = ref<number | null>(null);

function onDragStart(index: number): void {
    draggedIndex.value = index;
}

function onDragOver(index: number): void {
    dropTargetIndex.value = index;
}

function onDrop(index: number): void {
    if (draggedIndex.value !== null) {
        moveProject(draggedIndex.value, index);
    }

    draggedIndex.value = null;
    dropTargetIndex.value = null;
}

function onDragEnd(): void {
    draggedIndex.value = null;
    dropTargetIndex.value = null;
}

/** Équivalent clavier du glisser-déposer, requis pour l'accessibilité. */
function onHandleKeydown(event: KeyboardEvent, index: number): void {
    const offset =
        event.key === 'ArrowUp' ? -1 : event.key === 'ArrowDown' ? 1 : 0;

    if (offset === 0) {
        return;
    }

    event.preventDefault();
    moveProject(index, index + offset);
}
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
            <p class="text-muted-foreground text-sm">
                {{ projects.length }} projet{{ projects.length > 1 ? 's' : '' }}
            </p>

            <Card class="gap-0 overflow-hidden py-0">
                <ul>
                    <li
                        v-for="(project, index) in projects"
                        :key="project.id"
                        class="hover:bg-muted/30 flex items-start gap-3 border-b p-4 transition-colors last:border-b-0 sm:gap-4"
                        :class="{
                            'opacity-50': draggedIndex === index,
                            'bg-accent/60':
                                dropTargetIndex === index &&
                                draggedIndex !== index,
                        }"
                        draggable="true"
                        @dragstart="onDragStart(index)"
                        @dragover.prevent="onDragOver(index)"
                        @drop.prevent="onDrop(index)"
                        @dragend="onDragEnd"
                    >
                        <button
                            type="button"
                            class="text-muted-foreground hover:text-foreground focus-visible:ring-ring/50 mt-1 cursor-grab rounded-sm focus-visible:ring-[3px] focus-visible:outline-none"
                            :aria-label="`Déplacer ${project.title}. Utilisez les flèches haut et bas.`"
                            @keydown="onHandleKeydown($event, index)"
                        >
                            <GripVerticalIcon
                                class="size-4"
                                aria-hidden="true"
                            />
                        </button>

                        <!-- Première capture du projet, ou une vignette neutre. -->
                        <div
                            class="bg-muted text-muted-foreground hidden h-16 w-24 flex-none items-center justify-center overflow-hidden rounded-md border sm:flex"
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
                                    class="focus-visible:ring-ring/50 truncate rounded-sm text-left font-medium hover:underline focus-visible:ring-[3px] focus-visible:outline-none"
                                    @click="openEditProject(project)"
                                >
                                    {{ project.title }}
                                </button>
                                <Badge
                                    v-if="!project.date_end"
                                    variant="secondary"
                                >
                                    En cours
                                </Badge>
                            </div>
                            <p class="text-muted-foreground text-sm">
                                {{
                                    [
                                        project.organization,
                                        project.responsibilities,
                                        formatPeriod(
                                            project.date_start,
                                            project.date_end,
                                        ),
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

                        <Button
                            variant="ghost"
                            size="icon"
                            :aria-label="`Modifier ${project.title}`"
                            :title="`Modifier ${project.title}`"
                            @click="openEditProject(project)"
                        >
                            <PencilIcon aria-hidden="true" />
                        </Button>
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
