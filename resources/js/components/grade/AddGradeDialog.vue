<script setup lang="ts">
import GradeForm from '@/components/grade/GradeForm.vue';
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
import { useAddGradeDialog } from '@/composables/useAddGradeDialog';

const FORM_ID = 'add-grade-dialog-form';

const { isOpen, close } = useAddGradeDialog();
</script>

<template>
    <!--
        Le contenu est démonté à la fermeture : le formulaire repart vide à
        chaque ouverture, sans remise à zéro manuelle.
    -->
    <Dialog v-model:open="isOpen">
        <DialogContent
            class="flex max-h-[90svh] flex-col gap-0 p-0 sm:max-w-xl"
        >
            <DialogHeader class="border-b p-6">
                <DialogTitle>Ajouter une note</DialogTitle>
                <DialogDescription>
                    Renseignez les informations de la note pour l'ajouter à
                    votre carnet.
                </DialogDescription>
            </DialogHeader>

            <div class="overflow-y-auto p-6">
                <GradeForm :id="FORM_ID" @saved="close" />
            </div>

            <DialogFooter class="border-t p-6">
                <DialogClose as-child>
                    <Button type="button" variant="outline">Annuler</Button>
                </DialogClose>
                <Button type="submit" :form="FORM_ID">
                    Enregistrer la note
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
