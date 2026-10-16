<script lang="ts" setup>
import {
    CheckIcon,
    ChevronsUpDownIcon,
    MinusIcon,
    PlusIcon,
    UploadIcon,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
    ComboboxViewport,
} from '@/components/ui/combobox';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from '@/components/ui/input-group';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { type EditedGrade, useGradeForm } from '@/composables/useGradeForm';
import { MONTHS } from '@/lib/date';
import { GRADE_MAX, GRADE_MIN, GRADE_STEP } from '@/lib/grade-scale';
import type { GradeTree } from '@/lib/gradebook';

const props = defineProps<{
    /**
     * Identifiant du <form>, pour qu'un bouton placé ailleurs (pied de carte,
     * pied de fenêtre) puisse le soumettre via l'attribut `form`.
     */
    id: string;
    /** Arbre de la filière : ses feuilles sont les branches proposées. */
    tree: GradeTree | null;
    /** Note à modifier ; absente pour un ajout. */
    edited?: EditedGrade;
}>();

const emit = defineEmits<{
    /** Note enregistrée : le parent décide de la suite (fermer, rediriger…). */
    saved: [];
}>();

const {
    form,
    groups,
    branches,
    selectedBranch,
    keepsFile,
    dateDay,
    dateMonth,
    dateYear,
    fileInput,
    decrementGrade,
    incrementGrade,
    handleDrop,
    switchToOral,
    onFileChange,
    submit,
} = useGradeForm(() => props.tree, props.edited);

function onSubmit(): void {
    submit(() => emit('saved'));
}

// Le parent désactive son bouton d'envoi pendant l'enregistrement, et la
// lecture automatique pré-remplit puis envoie le formulaire.
defineExpose({ form, branches, submit: onSubmit });
</script>

<template>
    <form :id="id" novalidate @submit.prevent="onSubmit">
        <FieldGroup>
            <Field :data-invalid="Boolean(form.errors.average_node_id)">
                <!-- Les feuilles de l'arbre de la filière, groupées par domaine. -->
                <FieldLabel for="branche">Branche</FieldLabel>
                <Combobox v-model="selectedBranch" by="id">
                    <ComboboxAnchor as-child>
                        <ComboboxTrigger as-child>
                            <Button
                                id="branche"
                                type="button"
                                variant="outline"
                                class="w-full justify-between font-normal"
                                :aria-invalid="
                                    Boolean(form.errors.average_node_id)
                                "
                            >
                                <span class="truncate">
                                    {{
                                        selectedBranch?.name ??
                                        'Sélectionner une branche'
                                    }}
                                </span>
                                <ChevronsUpDownIcon class="opacity-50" />
                            </Button>
                        </ComboboxTrigger>
                    </ComboboxAnchor>
                    <ComboboxList side="bottom" :avoid-collisions="false">
                        <ComboboxInput placeholder="Rechercher une branche…" />
                        <ComboboxEmpty>Aucune branche trouvée.</ComboboxEmpty>
                        <!-- Un seul défilement : sinon chaque groupe défile seul et les groupes se chevauchent. -->
                        <ComboboxViewport>
                            <ComboboxGroup
                                v-for="group in groups"
                                :key="group.name"
                                :heading="group.name"
                            >
                                <ComboboxItem
                                    v-for="branch in group.branches"
                                    :key="branch.id"
                                    :value="branch"
                                >
                                    {{ branch.name }}
                                    <ComboboxItemIndicator>
                                        <CheckIcon />
                                    </ComboboxItemIndicator>
                                </ComboboxItem>
                            </ComboboxGroup>
                        </ComboboxViewport>
                    </ComboboxList>
                </Combobox>
                <FieldError :errors="[form.errors.average_node_id]" />
            </Field>

            <div class="grid gap-6 sm:grid-cols-2">
                <Field :data-invalid="Boolean(form.errors.value)">
                    <FieldLabel for="note">Note obtenue</FieldLabel>
                    <InputGroup>
                        <InputGroupAddon>
                            <InputGroupButton
                                type="button"
                                size="icon-xs"
                                @click="decrementGrade"
                            >
                                <MinusIcon />
                            </InputGroupButton>
                        </InputGroupAddon>
                        <InputGroupInput
                            id="note"
                            v-model="form.value"
                            type="number"
                            :min="GRADE_MIN"
                            :max="GRADE_MAX"
                            :step="GRADE_STEP"
                            class="text-center font-semibold"
                            :aria-invalid="Boolean(form.errors.value)"
                        />
                        <InputGroupAddon align="inline-end">
                            <InputGroupButton
                                type="button"
                                size="icon-xs"
                                @click="incrementGrade"
                            >
                                <PlusIcon />
                            </InputGroupButton>
                        </InputGroupAddon>
                    </InputGroup>
                    <FieldDescription
                        >De {{ GRADE_MIN.toFixed(1) }} à
                        {{ GRADE_MAX.toFixed(1) }}, par pas de
                        {{ GRADE_STEP.toFixed(1) }}</FieldDescription
                    >
                    <FieldError :errors="[form.errors.value]" />
                </Field>

                <Field :data-invalid="Boolean(form.errors.test_date)">
                    <FieldLabel for="date-day">Date du test</FieldLabel>
                    <div class="flex gap-2">
                        <Input
                            id="date-day"
                            v-model="dateDay"
                            type="number"
                            :min="1"
                            :max="31"
                            placeholder="Jour"
                            class="w-20"
                        />
                        <Select v-model="dateMonth">
                            <SelectTrigger id="date-month" class="flex-1">
                                <SelectValue placeholder="Mois" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem
                                        v-for="month in MONTHS"
                                        :key="month.value"
                                        :value="month.value"
                                    >
                                        {{ month.label }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <Input
                            id="date-year"
                            v-model="dateYear"
                            type="number"
                            :min="1900"
                            :max="2100"
                            placeholder="Année"
                            class="w-24"
                        />
                    </div>
                    <FieldError :errors="[form.errors.test_date]" />
                </Field>
            </div>

            <Field :data-invalid="Boolean(form.errors.file)">
                <FieldLabel>Justificatif</FieldLabel>
                <div class="mb-2 flex items-center gap-2">
                    <Switch
                        id="is-oral"
                        :model-value="form.is_oral"
                        @update:model-value="switchToOral"
                    />
                    <FieldLabel for="is-oral" class="font-normal"
                        >Épreuve orale</FieldLabel
                    >
                </div>

                <FieldDescription v-if="form.is_oral">
                    Aucun document n'est requis pour une épreuve orale.
                    <template v-if="edited?.hasFile">
                        Le scan déjà déposé sera supprimé.
                    </template>
                </FieldDescription>

                <div
                    v-else
                    class="hover:bg-muted/50 flex flex-col items-center justify-center gap-1 rounded-md border border-dashed p-8 text-center transition-colors"
                    :class="form.errors.file && 'border-destructive'"
                    @dragover.prevent
                    @drop="handleDrop"
                >
                    <UploadIcon class="text-muted-foreground size-5" />
                    <p class="text-sm">
                        Glisser le scan du test ici, ou
                        <button
                            type="button"
                            class="text-primary underline underline-offset-2"
                            @click="fileInput?.click()"
                        >
                            parcourir
                        </button>
                    </p>
                    <p class="text-muted-foreground text-xs">
                        PDF uniquement, 10 Mo maximum
                    </p>
                    <p v-if="form.file" class="text-foreground text-xs">
                        {{ form.file.name }}
                    </p>
                    <p v-else-if="keepsFile" class="text-foreground text-xs">
                        Le scan actuel est conservé, sauf si vous en déposez un
                        autre.
                    </p>
                    <input
                        ref="fileInput"
                        type="file"
                        accept="application/pdf"
                        class="hidden"
                        @change="onFileChange"
                    />
                </div>
                <FieldError :errors="[form.errors.file]" />
            </Field>
        </FieldGroup>
    </form>
</template>
