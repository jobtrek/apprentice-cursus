<script setup lang="ts">
import { Trash2Icon } from '@lucide/vue';
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
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { usePortfolio } from '@/composables/usePortfolio';
import { useProjectDialog } from '@/composables/useProjectDialog';

const FORM_ID = 'project-dialog-form';

const { isOpen, project, close } = useProjectDialog();
const { deleteProject } = usePortfolio();

function onDelete(): void {
    if (!project.value) {
        return;
    }

    deleteProject(project.value.id);
    close();
}
</script>

<template>
    <!--
        Création quand `project` est vide, modification sinon. Le contenu est
        démonté à la fermeture : le formulaire repart de `project` à chaque
        ouverture, sans remise à zéro manuelle.
    -->
    <Dialog v-model:open="isOpen">
        <DialogContent
            class="flex max-h-[90svh] flex-col gap-0 p-0 sm:max-w-2xl"
        >
            <DialogHeader class="border-b p-6">
                <DialogTitle>
                    {{ project ? 'Modifier le projet' : 'Nouveau projet' }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        project
                            ? project.title
                            : "Décrivez le projet pour l'ajouter à votre portfolio."
                    }}
                </DialogDescription>
            </DialogHeader>

            <div class="overflow-y-auto p-6">
                <ProjectForm
                    :id="FORM_ID"
                    :key="project?.id ?? 'new'"
                    :project="project"
                    @saved="close"
                />
            </div>

            <DialogFooter class="border-t p-6">
                <AlertDialog v-if="project">
                    <AlertDialogTrigger as-child>
                        <Button
                            type="button"
                            variant="ghost"
                            class="text-destructive hover:bg-destructive/10 hover:text-destructive sm:mr-auto"
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

                <DialogClose as-child>
                    <Button type="button" variant="outline">Annuler</Button>
                </DialogClose>
                <Button
                    type="submit"
                    :form="FORM_ID"
                    data-test="save-project-button"
                >
                    Enregistrer le projet
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
