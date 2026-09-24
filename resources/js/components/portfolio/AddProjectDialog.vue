<script setup lang="ts">
import ProjectForm from '@/components/portfolio/ProjectForm.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useAddProjectDialog } from '@/composables/useAddProjectDialog';

const FORM_ID = 'add-project-dialog-form';

const { isOpen, close } = useAddProjectDialog();
</script>

<template>
    <!--
        Le contenu est démonté à la fermeture : le formulaire repart vide à
        chaque ouverture, sans remise à zéro manuelle.
    -->
    <Dialog v-model:open="isOpen">
        <DialogContent
            class="flex max-h-[90svh] flex-col gap-0 p-0 sm:max-w-2xl"
        >
            <DialogHeader class="border-b p-6">
                <DialogTitle>Nouveau projet</DialogTitle>
                <DialogDescription>
                    Décrivez le projet pour l'ajouter à votre portfolio.
                </DialogDescription>
            </DialogHeader>

            <div class="overflow-y-auto p-6">
                <ProjectForm :id="FORM_ID" @saved="close" />
            </div>

            <DialogFooter class="border-t p-6">
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
