<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import GradeForm from '@/components/grade/GradeForm.vue';
import OcrGradeForm from '@/components/grade/OcrGradeForm.vue';
import SwitchButtonLayout from '@/components/SwitchButtonLayout.vue';
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
import type { GradeTree } from '@/lib/gradebook';
import { ref, watch } from 'vue';

defineProps<{
    tree: GradeTree | null;
}>();

const FORM_ID = 'add-grade-dialog-form';

const { isOpen, close } = useAddGradeDialog();
const page = usePage();

const gradeForm = ref<InstanceType<typeof GradeForm> | null>(null);
const ocrPanel = ref<InstanceType<typeof OcrGradeForm> | null>(null);

const useOcr = ref(false);

function fillForm(): void {
    const form = gradeForm.value?.form;
    const result = ocrPanel.value?.result;
    if (!form || !result) return;

    form.file = result.file;
    form.ocr_scan_id = result.scanId;
    form.ocr_node_id = result.branchId;
    form.average_node_id = result.branchId ?? form.average_node_id;
    if (result.grade != null) {
        form.value = result.grade;
    }
}

function approve(): void {
    fillForm();
    gradeForm.value?.submit();
}

function deny(): void {
    fillForm();
    useOcr.value = false;
}

watch(
    () => gradeForm.value?.form.hasErrors,
    (hasErrors) => {
        if (hasErrors) useOcr.value = false;
    },
);
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent
            class="flex max-h-[90svh] flex-col gap-0 p-0 sm:max-w-xl"
        >
            <DialogHeader class="border-b p-6">
                <DialogTitle>Ajouter une note</DialogTitle>
                <DialogDescription>
                    {{
                        useOcr
                            ? 'Déposez le PDF du test pour détecter la note automatiquement.'
                            : "Renseignez les informations de la note pour l'ajouter à votre carnet."
                    }}
                </DialogDescription>
            </DialogHeader>

            <div class="flex flex-col gap-6 overflow-y-auto p-6">
                <SwitchButtonLayout
                    v-if="page.props.auth.can.useOcr"
                    id="use-ocr"
                    v-model="useOcr"
                    label="Détecter la note automatiquement (OCR)"
                />

                <OcrGradeForm
                    v-show="useOcr"
                    ref="ocrPanel"
                    :branches="gradeForm?.branches ?? []"
                />

                <GradeForm
                    v-show="!useOcr"
                    :id="FORM_ID"
                    ref="gradeForm"
                    :tree="tree"
                    @saved="close"
                />
            </div>

            <DialogFooter class="border-t p-6">
                <DialogClose as-child>
                    <Button type="button" variant="outline">Annuler</Button>
                </DialogClose>
                <div v-if="useOcr && ocrPanel?.reading">
                    <Button type="button" variant="outline" @click="deny">
                        Refuser et modifier
                    </Button>
                    <Button
                        v-if="ocrPanel.canApprove"
                        type="button"
                        :disabled="gradeForm?.form.processing"
                        @click="approve"
                    >
                        Approuver
                    </Button>
                </div>
                <Button
                    v-else-if="useOcr"
                    type="button"
                    :disabled="!ocrPanel?.canAnalyse"
                    @click="ocrPanel?.analyse()"
                >
                    {{
                        ocrPanel?.processing
                            ? 'Analyse en cours…'
                            : 'Analyser le PDF'
                    }}
                </Button>
                <Button
                    v-if="!useOcr"
                    type="submit"
                    :form="FORM_ID"
                    :disabled="gradeForm?.form.processing"
                >
                    Enregistrer la note
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
