<script setup lang="ts">
import { TriangleAlertIcon } from '@lucide/vue';
import { useHttp } from '@inertiajs/vue3';
import UploadZoneLayout from '@/components/UploadZoneLayout.vue';
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

const canPickFile = computed(() => ocr.consent && !ocr.processing);

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

        <UploadZoneLayout
            as="label"
            :disabled="!canPickFile"
            :class="canPickFile && 'cursor-pointer'"
            hint="PDF uniquement, 10 Mo maximum"
            :file-name="ocr.file?.name"
        >
            Déposer le PDF du test
            <template #extra>
                <input
                    type="file"
                    accept="application/pdf"
                    class="hidden"
                    :disabled="!canPickFile"
                    @change="onOcrFileChange"
                />
            </template>
        </UploadZoneLayout>
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
