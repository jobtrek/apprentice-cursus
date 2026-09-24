<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Trash2Icon } from '@lucide/vue';
import { computed } from 'vue';
import { PageContainer, PageHeader } from '@/components/page';
import ProjectForm from '@/components/portfolio/ProjectForm.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { usePortfolio } from '@/composables/usePortfolio';
import portfolio from '@/routes/portfolio';

// Modification d'un projet. La création passe par <AddProjectDialog> sur la
// page Portfolio.
const props = defineProps<{
    projectId: number;
}>();

const FORM_ID = 'edit-project-form';

const { findProject, deleteProject } = usePortfolio();

const project = computed(() => findProject(props.projectId));

const breadcrumbs = [{ label: 'Portfolio', href: portfolio.index() }];

function backToPortfolio(): void {
    router.visit(portfolio.index());
}

function onDelete(): void {
    if (!project.value) {
        return;
    }

    deleteProject(project.value.id);
    backToPortfolio();
}
</script>

<template>
    <Head title="Modifier un projet" />

    <PageContainer size="sm">
        <PageHeader
            :title="project?.title ?? 'Modifier un projet'"
            :breadcrumbs="breadcrumbs"
        />

        <Card>
            <CardContent>
                <ProjectForm
                    :id="FORM_ID"
                    :project="project"
                    @saved="backToPortfolio"
                />
            </CardContent>

            <CardFooter class="flex-wrap gap-2 border-t">
                <Button
                    type="submit"
                    :form="FORM_ID"
                    data-test="save-project-button"
                >
                    Enregistrer le projet
                </Button>
                <Button as-child type="button" variant="outline">
                    <Link :href="portfolio.index()">Annuler</Link>
                </Button>
                <AlertDialog v-if="project">
                    <AlertDialogTrigger as-child>
                        <Button
                            type="button"
                            variant="ghost"
                            class="text-destructive hover:bg-destructive/10 hover:text-destructive ml-auto"
                        >
                            <Trash2Icon aria-hidden="true" />
                            Supprimer
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                Supprimer « {{ project.title }} » ?
                            </AlertDialogTitle>
                            <AlertDialogDescription>
                                Cette action est irréversible. Le projet sera
                                retiré de votre portfolio.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Annuler</AlertDialogCancel>
                            <AlertDialogAction
                                :class="
                                    buttonVariants({ variant: 'destructive' })
                                "
                                @click="onDelete"
                            >
                                Supprimer
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </CardFooter>
        </Card>
    </PageContainer>
</template>
