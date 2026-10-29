<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import GradeCategoryTabs from '@/components/grade/GradeCategoryTabs.vue';
import GradeForm from '@/components/grade/GradeForm.vue';
import OcrGradeForm from '@/components/grade/OcrGradeForm.vue';
import DialogCancelButton from '@/components/DialogCancelButton.vue';
import SwitchButtonLayout from '@/components/SwitchButtonLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { TabsTrigger } from '@/components/ui/tabs';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useAddGradeDialog } from '@/composables/useAddGradeDialog';
import type { GradeTree } from '@/lib/gradebook';
import { ref, watch } from 'vue';
import OcrFooterButtons from '@/components/grade/OcrFooterButtons.vue';

defineProps<{
    tree: GradeTree | null;
}>();

const FORM_ID = 'add-grade-dialog-form';

const { isOpen, close } = useAddGradeDialog();
const page = usePage();

const gradeForm = ref<InstanceType<typeof GradeForm> | null>(null);
const ocrPanel = ref<InstanceType<typeof OcrGradeForm> | null>(null);

const useOcr = ref(false);

type CategoryTab = 'general' | 'it';

const tab = ref<CategoryTab>('general');
const kind = ref<'school' | 'cie'>('school');

function selectKind(value: unknown): void {
    if (value === 'school' || value === 'cie') kind.value = value;
}

watch(tab, (value) => {
    if (value === 'it') useOcr.value = false;
});

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
            class="flex h-[min(90svh,40rem)] flex-col gap-0 p-0 sm:max-w-xl"
        >
            <DialogHeader class="p-6">
                <DialogTitle>Ajouter une note</DialogTitle>
                <DialogDescription>
                    {{
                        useOcr
                            ? 'Déposez le PDF du test pour détecter la note automatiquement.'
                            : "Renseignez les informations de la note pour l'ajouter à votre carnet."
                    }}
                </DialogDescription>
            </DialogHeader>

            <GradeCategoryTabs v-model="tab">
                <TabsTrigger
                    value="general"
                    class="data-[state=inactive]:text-muted-foreground data-[state=active]:border-primary dark:data-[state=active]:border-primary -mb-px h-auto flex-none rounded-none border-0 border-b-2 px-1 py-3 whitespace-normal transition-colors data-[state=active]:bg-transparent data-[state=active]:shadow-none dark:data-[state=active]:bg-transparent"
                >
                    Culture générale et compétences de base élargies
                </TabsTrigger>
                <TabsTrigger
                    value="it"
                    class="data-[state=inactive]:text-muted-foreground data-[state=active]:border-primary dark:data-[state=active]:border-primary -mb-px h-auto flex-none rounded-none border-0 border-b-2 px-1 py-3 whitespace-normal transition-colors data-[state=active]:bg-transparent data-[state=active]:shadow-none dark:data-[state=active]:bg-transparent"
                >
                    Compétences informatiques
                </TabsTrigger>
            </GradeCategoryTabs>

            <main
                class="flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto p-6"
            >
                <div class="flex h-8 shrink-0 items-center">
                    <ToggleGroup
                        v-if="tab === 'it'"
                        type="single"
                        variant="outline"
                        size="sm"
                        :spacing="2"
                        :model-value="kind"
                        aria-label="Type de module"
                        @update:model-value="selectKind"
                    >
                        <ToggleGroupItem
                            value="school"
                            @pointerdown.left="kind = 'school'"
                            class="data-[state=on]:bg-primary data-[state=on]:text-primary-foreground rounded-full px-4 transition-none"
                        >
                            École professionnelle
                        </ToggleGroupItem>
                        <ToggleGroupItem
                            value="cie"
                            @pointerdown.left="kind = 'cie'"
                            class="data-[state=on]:bg-primary data-[state=on]:text-primary-foreground rounded-full px-4 transition-none"
                        >
                            Cours interentreprises (CIE)
                        </ToggleGroupItem>
                    </ToggleGroup>
                    <SwitchButtonLayout
                        v-else-if="page.props.auth.can.useOcr"
                        id="use-ocr"
                        v-model="useOcr"
                        label="Détecter la note automatiquement (OCR)"
                    />
                </div>

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
                    :without-file="tab === 'it'"
                    @saved="close"
                />
            </main>

            <DialogFooter class="border-t p-6">
                <DialogCancelButton />
                <OcrFooterButtons
                    v-if="useOcr"
                    :scan-status="ocrPanel?.scanStatus ?? 'incomplete'"
                    :saving="!!gradeForm?.form.processing"
                    @analyse="ocrPanel?.analyse()"
                    @approve="approve"
                    @deny="deny"
                />
                <Button
                    v-else
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
