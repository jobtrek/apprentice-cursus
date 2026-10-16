<script setup lang="ts">
import { TriangleAlertIcon, UploadIcon } from '@lucide/vue';
import { useHttp, usePage } from '@inertiajs/vue3';
import GradeForm from '@/components/grade/GradeForm.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Field, FieldLabel } from '@/components/ui/field';
import { Switch } from '@/components/ui/switch';
import { useAddGradeDialog } from '@/composables/useAddGradeDialog';
import type { GradeTree } from '@/lib/gradebook';
import grades from '@/routes/grades';
import { computed, ref, watch } from 'vue';
import type { ScanReading } from '@/types/scan';

defineProps<{
    /** Arbre de la filière de l'apprenti·e, envoyé par la page. */
    tree: GradeTree | null;
}>();

const FORM_ID = 'add-grade-dialog-form';

const { isOpen, close } = useAddGradeDialog();
const page = usePage();

const gradeForm = ref<InstanceType<typeof GradeForm> | null>(null);

// Lecture automatique (OCR) : facultative, et bloquée tant que l'apprenti·e
// n'a pas accepté la collecte de ses données.
const useOcr = ref(false);

const ocr = useHttp<{ consent: boolean; file: File | null }, ScanReading>({
    consent: false,
    file: null,
});
const reading = ref<ScanReading | null>(null);

// Le choix du fichier est bloqué pendant l'analyse : le résultat reçu
// correspond toujours à `ocr.file`, le PDF joint à la note.
function onOcrFileChange(event: Event): void {
    ocr.file = (event.target as HTMLInputElement).files?.[0] ?? null;
    reading.value = null;
}

function analyse(): void {
    ocr.post(grades.readScan().url, {
        onSuccess: (data) => {
            reading.value = data;
        },
    });
}

// ponytail: seules les branches qui portent le nom d'une matière sont
// reconnues ; ajouter une entrée ici quand l'arbre en a d'autres.
const SUBJECT_BRANCHES: Record<string, string> = {
    'subjects/Anglais': 'Anglais',
    'subjects/Maths': 'Mathématiques',
};

const readingBranch = computed(() => {
    const name = reading.value && SUBJECT_BRANCHES[reading.value.subject];
    return gradeForm.value?.branches.find((branch) => branch.name === name);
});

// Approuver demande une note et une branche reconnue ; sinon il faut compléter.
const canApprove = computed(
    () => reading.value?.grade != null && readingBranch.value !== undefined,
);

// Le résultat pré-remplit le formulaire (date du test : aujourd'hui) ; le PDF
// sert de justificatif.
function fillForm(): void {
    const form = gradeForm.value?.form;
    if (!form || !ocr.file) return;

    form.file = ocr.file;
    // Pour le tableau de bord OCR : la note enregistrée est comparée au scan.
    form.ocr_scan_id = reading.value?.scan_id ?? null;
    form.ocr_node_id = readingBranch.value?.id ?? null;
    form.average_node_id = readingBranch.value?.id ?? form.average_node_id;
    if (reading.value?.grade != null) {
        form.value = reading.value.grade;
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

// Une erreur (client ou serveur) après « Approuver » : le formulaire est
// affiché pour que l'apprenti·e la corrige.
watch(
    () => gradeForm.value?.form.hasErrors,
    (hasErrors) => {
        if (hasErrors) useOcr.value = false;
    },
);

const subjectLabel = (subject: string): string =>
    subject === 'OTHER' ? 'Autre' : subject.replace('subjects/', '');
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
                    {{
                        useOcr
                            ? 'Déposez le PDF du test pour détecter la note automatiquement.'
                            : "Renseignez les informations de la note pour l'ajouter à votre carnet."
                    }}
                </DialogDescription>
            </DialogHeader>

            <div class="flex flex-col gap-6 overflow-y-auto p-6">
                <div
                    v-if="page.props.auth.can.useOcr"
                    class="flex items-center gap-2"
                >
                    <Switch id="use-ocr" v-model="useOcr" />
                    <FieldLabel for="use-ocr" class="font-normal">
                        Détecter la note automatiquement (OCR)
                    </FieldLabel>
                </div>

                <div v-if="useOcr" class="flex flex-col gap-4">
                    <div
                        role="alert"
                        class="flex gap-3 rounded-md border border-amber-500/50 bg-amber-500/10 p-4 text-sm"
                    >
                        <TriangleAlertIcon
                            class="size-5 shrink-0 text-amber-600"
                        />
                        <p>
                            Le document que vous déposez est envoyé à un service
                            externe pour être analysé : vos données sont
                            collectées.
                        </p>
                    </div>

                    <Field orientation="horizontal">
                        <Checkbox id="ocr-consent" v-model="ocr.consent" />
                        <FieldLabel for="ocr-consent" class="font-normal">
                            J'accepte que mes données soient collectées
                        </FieldLabel>
                    </Field>

                    <label
                        class="flex flex-col items-center justify-center gap-1 rounded-md border border-dashed p-8 text-center transition-colors"
                        :class="
                            ocr.consent && !ocr.processing
                                ? 'hover:bg-muted/50 cursor-pointer'
                                : 'cursor-not-allowed opacity-50'
                        "
                    >
                        <UploadIcon class="text-muted-foreground size-5" />
                        <span class="text-sm">Déposer le PDF du test</span>
                        <span class="text-muted-foreground text-xs">
                            PDF uniquement, 10 Mo maximum
                        </span>
                        <span v-if="ocr.file" class="text-foreground text-xs">
                            {{ ocr.file.name }}
                        </span>
                        <input
                            type="file"
                            accept="application/pdf"
                            class="hidden"
                            :disabled="!ocr.consent || ocr.processing"
                            @change="onOcrFileChange"
                        />
                    </label>
                    <p
                        v-if="ocr.errors.file || ocr.errors.consent"
                        class="text-destructive text-sm"
                    >
                        {{ ocr.errors.file ?? ocr.errors.consent }}
                    </p>

                    <div
                        v-if="reading"
                        role="status"
                        class="flex flex-col gap-1 rounded-md border p-4 text-sm"
                    >
                        <p>
                            Matière :
                            <strong>{{ subjectLabel(reading.subject) }}</strong>
                            <span
                                v-if="reading.confidence !== null"
                                class="text-muted-foreground"
                            >
                                (confiance
                                {{ Math.round(reading.confidence * 100) }} %)
                            </span>
                        </p>
                        <p>
                            Branche :
                            <strong>{{
                                readingBranch?.name ?? 'non reconnue'
                            }}</strong>
                        </p>
                        <p>
                            Note :
                            <strong>{{
                                reading.grade?.toFixed(1) ?? 'non trouvée'
                            }}</strong>
                        </p>
                        <p class="text-muted-foreground text-xs">
                            Analysé en
                            {{ (reading.duration_ms / 1000).toFixed(1) }} s
                        </p>
                        <p v-if="reading.flagged" class="text-amber-600">
                            Résultat incertain : vérifiez-le avant
                            d'enregistrer.
                        </p>
                    </div>
                </div>

                <!-- v-show : la saisie manuelle est conservée si on rebascule. -->
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
                <template v-if="useOcr && reading">
                    <Button type="button" variant="outline" @click="deny">
                        Refuser et modifier
                    </Button>
                    <Button
                        v-if="canApprove"
                        type="button"
                        :disabled="gradeForm?.form.processing"
                        @click="approve"
                    >
                        Approuver
                    </Button>
                </template>
                <Button
                    v-else-if="useOcr"
                    type="button"
                    :disabled="!ocr.consent || !ocr.file || ocr.processing"
                    @click="analyse"
                >
                    {{
                        ocr.processing ? 'Analyse en cours…' : 'Analyser le PDF'
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
