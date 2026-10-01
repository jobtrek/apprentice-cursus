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
import AddActionButton from '@/components/AddActionButton.vue';
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
            description="Glissez un projet pour changer son ordre d'affichage dans l'aperçu exportable."
        >
            <template #actions>
                <Button as-child variant="outline">
                    <Link :href="portfolio.preview()">
                        <EyeIcon aria-hidden="true" />
                        Aperçu
                    </Link>
                </Button>
                <AddActionButton
                    label="Nouveau projet"
                    data-test="new-project-button"
                    @click="openAddProject"
                />
            </template>
        </PageHeader>

        <Card v-if="projects.length > 0" class="gap-0 overflow-hidden py-0">
            <ul>
                <li
                    v-for="(project, index) in projects"
                    :key="project.id"
                    class="flex items-center gap-4 border-b p-4 last:border-b-0"
                    :class="{
                        'opacity-50': draggedIndex === index,
                        'bg-accent/60':
                            dropTargetIndex === index && draggedIndex !== index,
                    }"
                    draggable="true"
                    @dragstart="onDragStart(index)"
                    @dragover.prevent="onDragOver(index)"
                    @drop.prevent="onDrop(index)"
                    @dragend="onDragEnd"
                >
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground focus-visible:ring-ring/50 cursor-grab rounded-sm focus-visible:ring-[3px] focus-visible:outline-none"
                        :aria-label="`Déplacer ${project.title}. Utilisez les flèches haut et bas.`"
                        @keydown="onHandleKeydown($event, index)"
                    >
                        <GripVerticalIcon class="size-4" aria-hidden="true" />
                    </button>

                    <div class="min-w-0 flex-1 space-y-1">
                        <p class="truncate font-medium">{{ project.title }}</p>
                        <p class="text-muted-foreground truncate text-sm">
                            <template v-if="project.organization">
                                {{ project.organization }} ·
                            </template>
                            {{
                                formatPeriod(
                                    project.date_start,
                                    project.date_end,
                                )
                            }}
                        </p>
                        <div class="flex flex-wrap gap-1 pt-1">
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
                        variant="outline"
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
