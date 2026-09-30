<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from '@/components/ui/input-group';
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
} from '@/components/ui/combobox';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { GRADE_MAX, GRADE_MIN, GRADE_STEP } from '@/constants/constants';
import { MONTHS, useGradeForm } from '@/composables/useGradeForm';
import { Head, Link, router } from '@inertiajs/vue3';
import { PageContainer, PageHeader } from '@/components/page';
import grades from '@/routes/grades';
import {
    CheckIcon,
    ChevronsUpDownIcon,
    MinusIcon,
    PlusIcon,
    UploadIcon,
} from '@lucide/vue';

const {
    subjects,
    MatureSubjects,
    isMp,
    isModuleTest,
    modules,
    isOral,
    grade,
    dateDay,
    dateMonth,
    dateYear,
    selectedSubject,
    selectedModule,
    selectedFile,
    fileInput,
    decrementGrade,
    incrementGrade,
    handleDrop,
    switchToOral,
    onFileChange,
    gradeMode,
    errors,
    validate,
} = useGradeForm();

// Tant que l'enregistrement côté serveur n'existe pas, une saisie valide
// ramène simplement au carnet de notes.
function onSubmit(): void {
    if (!validate()) {
        return;
    }

    router.visit(grades.dashboard());
}
</script>

<template>
    <Head title="Ajouter une note" />

    <PageContainer size="sm">
        <PageHeader
            title="Ajouter une note"
            description="Renseignez les informations de la note pour l'ajouter au dossier de l'apprenti·e."
        />

        <form novalidate @submit.prevent="onSubmit">
            <Card>
                <CardContent>
                    <FieldGroup>
                        <Field orientation="horizontal">
                            <FieldLabel id="grade-mode-label">
                                Type d'évaluation
                            </FieldLabel>
                            <ToggleGroup
                                v-model="gradeMode"
                                type="single"
                                variant="outline"
                                aria-labelledby="grade-mode-label"
                                class="ml-auto"
                            >
                                <ToggleGroupItem value="notes"
                                    >Notes</ToggleGroupItem
                                >
                                <ToggleGroupItem value="modules"
                                    >Modules</ToggleGroupItem
                                >
                            </ToggleGroup>
                        </Field>

                        <Field
                            v-if="!isModuleTest"
                            :data-invalid="Boolean(errors.subject)"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <FieldLabel for="matiere">Matière</FieldLabel>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="isMp = !isMp"
                                >
                                    <span v-if="isMp"
                                        >Voir les matières du CFC</span
                                    >
                                    <span v-else
                                        >Voir les matières de maturité</span
                                    >
                                </Button>
                            </div>
                            <Combobox v-model="selectedSubject" by="name">
                                <ComboboxAnchor as-child>
                                    <ComboboxTrigger as-child>
                                        <Button
                                            id="matiere"
                                            type="button"
                                            variant="outline"
                                            class="w-full justify-between font-normal"
                                        >
                                            {{
                                                selectedSubject?.name ??
                                                'Sélectionner une matière'
                                            }}
                                            <ChevronsUpDownIcon
                                                class="opacity-50"
                                            />
                                        </Button>
                                    </ComboboxTrigger>
                                </ComboboxAnchor>
                                <ComboboxList>
                                    <ComboboxInput
                                        placeholder="Rechercher une matière…"
                                    />
                                    <ComboboxEmpty
                                        >Aucune matière trouvée.</ComboboxEmpty
                                    >
                                    <ComboboxGroup>
                                        <ComboboxItem
                                            v-for="subject in isMp
                                                ? MatureSubjects
                                                : subjects"
                                            :key="subject.name"
                                            :value="subject"
                                        >
                                            {{ subject.name }}
                                            <ComboboxItemIndicator>
                                                <CheckIcon />
                                            </ComboboxItemIndicator>
                                        </ComboboxItem>
                                    </ComboboxGroup>
                                </ComboboxList>
                            </Combobox>
                            <FieldError :errors="[errors.subject]" />
                        </Field>

                        <Field v-else :data-invalid="Boolean(errors.module)">
                            <FieldLabel for="module">Module</FieldLabel>
                            <Combobox v-model="selectedModule" by="id">
                                <ComboboxAnchor as-child>
                                    <ComboboxTrigger as-child>
                                        <Button
                                            id="module"
                                            type="button"
                                            variant="outline"
                                            class="w-full justify-between font-normal"
                                        >
                                            {{
                                                selectedModule
                                                    ? `${selectedModule.code} — ${selectedModule.name} (${selectedModule.school})`
                                                    : 'Sélectionner un module'
                                            }}
                                            <ChevronsUpDownIcon
                                                class="opacity-50"
                                            />
                                        </Button>
                                    </ComboboxTrigger>
                                </ComboboxAnchor>
                                <ComboboxList
                                    side="bottom"
                                    :avoid-collisions="false"
                                >
                                    <ComboboxInput
                                        placeholder="Rechercher un module…"
                                    />
                                    <ComboboxEmpty
                                        >Aucun module trouvé.</ComboboxEmpty
                                    >
                                    <ComboboxGroup>
                                        <ComboboxItem
                                            v-for="module in modules"
                                            :key="module.id"
                                            :value="module"
                                        >
                                            {{ module.code }} —
                                            {{ module.name }} ({{
                                                module.school
                                            }})
                                            <ComboboxItemIndicator>
                                                <CheckIcon />
                                            </ComboboxItemIndicator>
                                        </ComboboxItem>
                                    </ComboboxGroup>
                                </ComboboxList>
                            </Combobox>
                            <FieldError :errors="[errors.module]" />
                        </Field>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <Field :data-invalid="Boolean(errors.grade)">
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
                                        v-model="grade"
                                        type="number"
                                        :min="GRADE_MIN"
                                        :max="GRADE_MAX"
                                        :step="GRADE_STEP"
                                        class="text-center font-semibold"
                                        :aria-invalid="Boolean(errors.grade)"
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
                                    {{
                                        GRADE_STEP.toFixed(1)
                                    }}</FieldDescription
                                >
                                <FieldError :errors="[errors.grade]" />
                            </Field>

                            <Field :data-invalid="Boolean(errors.date)">
                                <FieldLabel for="date-month"
                                    >Date du test</FieldLabel
                                >
                                <div class="flex gap-2">
                                    <Select v-model="dateMonth">
                                        <SelectTrigger
                                            id="date-month"
                                            class="flex-1"
                                        >
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
                                        id="date-day"
                                        v-model="dateDay"
                                        type="number"
                                        :min="1"
                                        :max="31"
                                        placeholder="Jour"
                                        class="w-20"
                                    />
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
                                <FieldError :errors="[errors.date]" />
                            </Field>
                        </div>

                        <Field
                            v-if="!isModuleTest"
                            :data-invalid="Boolean(errors.file)"
                        >
                            <FieldLabel>Justificatif</FieldLabel>
                            <div class="mb-2 flex items-center gap-2">
                                <Switch
                                    id="is-oral"
                                    :model-value="isOral"
                                    @update:model-value="switchToOral"
                                />
                                <FieldLabel for="is-oral" class="font-normal"
                                    >Épreuve orale</FieldLabel
                                >
                            </div>

                            <FieldDescription v-if="isOral">
                                Aucun document n'est requis pour une épreuve
                                orale.
                            </FieldDescription>

                            <div
                                v-else
                                class="hover:bg-muted/50 flex flex-col items-center justify-center gap-1 rounded-md border border-dashed p-8 text-center transition-colors"
                                :class="errors.file && 'border-destructive'"
                                @dragover.prevent
                                @drop="handleDrop"
                            >
                                <UploadIcon
                                    class="text-muted-foreground size-5"
                                />
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
                                <p
                                    v-if="selectedFile"
                                    class="text-foreground text-xs"
                                >
                                    {{ selectedFile.name }}
                                </p>
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept="application/pdf"
                                    class="hidden"
                                    @change="onFileChange"
                                />
                            </div>
                            <FieldError :errors="[errors.file]" />
                        </Field>
                    </FieldGroup>
                </CardContent>

                <CardFooter class="gap-2 border-t">
                    <Button type="submit">Enregistrer la note</Button>
                    <Button as-child type="button" variant="outline">
                        <Link :href="grades.dashboard()">Annuler</Link>
                    </Button>
                </CardFooter>
            </Card>
        </form>
    </PageContainer>
</template>
