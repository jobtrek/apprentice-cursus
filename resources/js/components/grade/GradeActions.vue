<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { PencilIcon, Trash2Icon } from '@lucide/vue';
import { ref } from 'vue';
import GradeForm from '@/components/grade/GradeForm.vue';
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
    DialogTrigger,
} from '@/components/ui/dialog';
import type { GradeTree } from '@/lib/gradebook';
import grades from '@/routes/grades';
import type { Grade } from '@/types/grade';

const props = defineProps<{
    grade: Grade;
    /** La note a un scan ; sinon c'est une épreuve orale. */
    hasFile: boolean;
    tree: GradeTree | null;
    can: { update: boolean; delete: boolean };
}>();

const FORM_ID = 'edit-grade-dialog-form';

const isEditing = ref(false);
const gradeForm = ref<InstanceType<typeof GradeForm> | null>(null);
const deleting = ref(false);

function onDelete(): void {
    router.delete(grades.destroy.url(props.grade.id), {
        onStart: () => (deleting.value = true),
        onFinish: () => (deleting.value = false),
    });
}
</script>

<template>
    <div class="flex gap-2">
        <!--
            Le contenu est démonté à la fermeture : le formulaire repart de la
            note enregistrée à chaque ouverture.
        -->
        <Dialog v-if="can.update" v-model:open="isEditing">
            <DialogTrigger as-child>
                <Button variant="outline" data-test="edit-grade-button">
                    <PencilIcon aria-hidden="true" />
                    Modifier
                </Button>
            </DialogTrigger>
            <DialogContent
                class="flex max-h-[90svh] flex-col gap-0 p-0 sm:max-w-xl"
            >
                <DialogHeader class="border-b p-6">
                    <DialogTitle>Modifier la note</DialogTitle>
                    <DialogDescription>
                        Les commentaires de votre coach et de votre formateur
                        restent attachés à la note.
                    </DialogDescription>
                </DialogHeader>

                <div class="overflow-y-auto p-6">
                    <GradeForm
                        :id="FORM_ID"
                        ref="gradeForm"
                        :tree="tree"
                        :edited="{ grade, hasFile }"
                        @saved="isEditing = false"
                    />
                </div>

                <DialogFooter class="border-t p-6">
                    <DialogClose as-child>
                        <Button type="button" variant="outline">
                            Annuler
                        </Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        :form="FORM_ID"
                        :disabled="gradeForm?.form.processing"
                    >
                        Enregistrer les modifications
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <AlertDialog v-if="can.delete">
            <AlertDialogTrigger as-child>
                <Button
                    variant="ghost"
                    :disabled="deleting"
                    class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                    data-test="delete-grade-button"
                >
                    <Trash2Icon aria-hidden="true" />
                    Supprimer
                </Button>
            </AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        Supprimer la note « {{ grade.title }} » ?
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        Cette action est irréversible. La note, son scan et ses
                        commentaires seront supprimés.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Annuler</AlertDialogCancel>
                    <AlertDialogAction
                        :class="buttonVariants({ variant: 'destructive' })"
                        :disabled="deleting"
                        @click="onDelete"
                    >
                        Supprimer
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
