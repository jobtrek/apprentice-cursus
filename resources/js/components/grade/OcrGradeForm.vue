<script setup lang="ts">
import { TriangleAlertIcon, UploadIcon } from '@lucide/vue';
import { useHttp } from '@inertiajs/vue3';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldLabel } from '@/components/ui/field';
import type { Branch } from '@/composables/useGradeForm';
import grades from '@/routes/grades';
import type { ScanReading, ScanResult, ScanStatus } from '@/types/scan';
import { computed, ref } from 'vue';

const props = defineProps<{
    branches: Branch[];
}>();

const ocr = useHttp<{ consent: boolean; file: File | null }, ScanReading>({
    consent: false,
    file: null,
});
const reading = ref<ScanReading | null>(null);

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

const SUBJECT_BRANCHES: Record<string, string> = {
    'subjects/Anglais': 'Anglais',
    'subjects/Maths': 'Mathématiques',
};

const readingBranch = computed(() => {
    const name = reading.value && SUBJECT_BRANCHES[reading.value.subject];
    return props.branches.find((branch) => branch.name === name);
});

const scanStatus = computed<ScanStatus>(() => {
    if (reading.value) {
        return reading.value.grade != null && readingBranch.value !== undefined
            ? 'approvable'
            : 'needsEdit';
    }
    if (ocr.processing) return 'analysing';
    return ocr.consent && ocr.file !== null ? 'ready' : 'incomplete';
});

const result = computed<ScanResult | null>(() =>
    reading.value && ocr.file
        ? {
              file: ocr.file,
              scanId: reading.value.scan_id,
              branchId: readingBranch.value?.id ?? null,
              grade: reading.value.grade,
          }
        : null,
);

const subjectLabel = (subject: string): string =>
    subject === 'OTHER' ? 'Autre' : subject.replace('subjects/', '');

defineExpose({ scanStatus, result, analyse });
</script>

<template>
    <form class="flex flex-col gap-4">
        <section
            role="alert"
            class="flex gap-3 rounded-md border border-amber-500/50 bg-amber-500/10 p-4 text-sm"
        >
            <TriangleAlertIcon class="size-5 shrink-0 text-amber-600" />
            <p>
                Le document que vous déposez est envoyé à un service externe
                pour être analysé : vos données sont collectées.
            </p>
        </section>

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

        <section
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
                <strong>{{ readingBranch?.name ?? 'non reconnue' }}</strong>
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
                Résultat incertain : vérifiez-le avant d'enregistrer.
            </p>
        </section>
    </form>
</template>
