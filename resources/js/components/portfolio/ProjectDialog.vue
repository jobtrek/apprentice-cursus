<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2Icon } from '@lucide/vue';
import { ref } from 'vue';
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
import { useProjectDialog } from '@/composables/useProjectDialog';
import portfolio from '@/routes/portfolio';
import type { Skill } from '@/types/portfolio';

defineProps<{
    skills: Skill[];
}>();

const FORM_ID = 'project-dialog-form';

const { isOpen, project, close } = useProjectDialog();

const projectForm = ref<InstanceType<typeof ProjectForm> | null>(null);
const deleting = ref(false);

function onDelete(): void {
    if (!project.value) {
        return;
    }

    router.delete(portfolio.projects.destroy.url(project.value.id), {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onSuccess: close,
        onFinish: () => (deleting.value = false),
    });
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
                    ref="projectForm"
                    :id="FORM_ID"
                    :key="project?.id ?? 'new'"
                    :project="project"
                    :skills="skills"
                    @saved="close"
                />
            </div>

            <DialogFooter class="border-t p-6">
                <AlertDialog v-if="project">
                    <AlertDialogTrigger as-child>
                        <Button
                            type="button"
                            variant="ghost"
                            :disabled="deleting"
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
                                :disabled="deleting"
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
                    :disabled="projectForm?.form.processing"
                    data-test="save-project-button"
                >
                    Enregistrer le projet
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
