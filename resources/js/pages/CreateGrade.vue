<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
} from '@/components/ui/card';
import {
    Field,
    FieldDescription,
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
import { Separator } from '@/components/ui/separator';
import { GRADE_MAX, GRADE_MIN, GRADE_STEP } from '@/constants/constants';
import { MONTHS, useGradeForm } from '@/composables/useGradeForm';
import { Link } from '@inertiajs/vue3';
import { CheckIcon, ChevronsUpDownIcon, MinusIcon, PlusIcon, UploadIcon } from '@lucide/vue';

const {
    subjects,
    MatureSubjects,
    isMp,
    isEpsic,
    isModuleTest,
    cieModules,
    epsicModules,
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
} = useGradeForm();
</script>

<template>
    <div class="flex flex-1 flex-col w-full justify-center items-center gap-4">
        <div>
            <h2 class="text-2xl font-semibold self-start">Ajouter une note</h2>
        </div>
        <form class="w-175">
        <Card>
            <CardHeader>
                <FieldGroup>
                    <div class="flex justify-end">
                        <ToggleGroup v-model="gradeMode" type="single" variant="outline">
                            <ToggleGroupItem value="notes">Notes</ToggleGroupItem>
                            <ToggleGroupItem value="modules-cie">Modules CIE</ToggleGroupItem>
                            <ToggleGroupItem value="modules-epsic">Modules EPSIC</ToggleGroupItem>
                        </ToggleGroup>
                    </div>

                    <Field v-if="!isModuleTest">
                        <div class="flex items-center justify-between gap-2">
                            <FieldLabel for="matiere">Matière</FieldLabel>
                            <Button type="button" variant="outline" size="sm" @click="isMp = !isMp">
                                <span v-if="isMp">Voir les matières du CFC Normale</span>
                                <span v-else>Voir les matières de maturité</span>
                            </Button>
                        </div>
                        <Combobox v-model="selectedSubject" by="name">
                            <ComboboxAnchor as-child>
                                <ComboboxTrigger as-child>
                                    <Button id="matiere" type="button" variant="outline" class="w-full justify-between font-normal">
                                        {{ selectedSubject?.name ?? 'Sélectionner une matière' }}
                                        <ChevronsUpDownIcon class="opacity-50" />
                                    </Button>
                                </ComboboxTrigger>
                            </ComboboxAnchor>
                            <ComboboxList>
                                <ComboboxInput placeholder="Rechercher une matière..." />
                                <ComboboxEmpty>Aucune matière trouvée.</ComboboxEmpty>
                                <ComboboxGroup>
                                    <ComboboxItem v-for="subject in isMp ? MatureSubjects : subjects" :key="subject.name"
                                        :value="subject">
                                        {{ subject.name }}
                                        <ComboboxItemIndicator>
                                            <CheckIcon />
                                        </ComboboxItemIndicator>
                                    </ComboboxItem>
                                </ComboboxGroup>
                            </ComboboxList>
                        </Combobox>
                    </Field>

                    <Field v-else>
                        <FieldLabel for="module">Module</FieldLabel>
                        <Combobox v-model="selectedModule" by="id">
                            <ComboboxAnchor as-child>
                                <ComboboxTrigger as-child>
                                    <Button id="module" type="button" variant="outline" class="w-full justify-between font-normal">
                                        {{ selectedModule ? `${selectedModule.code} — ${selectedModule.name}` : 'Sélectionner un module' }}
                                        <ChevronsUpDownIcon class="opacity-50" />
                                    </Button>
                                </ComboboxTrigger>
                            </ComboboxAnchor>
                            <ComboboxList>
                                <ComboboxInput placeholder="Rechercher un module..." />
                                <ComboboxEmpty>Aucun module trouvé.</ComboboxEmpty>
                                <ComboboxGroup>
                                    <ComboboxItem v-for="module in isEpsic ? epsicModules : cieModules"
                                        :key="module.id" :value="module">
                                        {{ module.code }} — {{ module.name }}
                                        <ComboboxItemIndicator>
                                            <CheckIcon />
                                        </ComboboxItemIndicator>
                                    </ComboboxItem>
                                </ComboboxGroup>
                            </ComboboxList>
                        </Combobox>
                    </Field>
                </FieldGroup>
            </CardHeader>

            <CardContent class="flex flex-col gap-6">
                <div class="flex gap-6">
                    <Field class="flex-1">
                        <FieldLabel for="note">Note obtenue</FieldLabel>
                        <InputGroup>
                            <InputGroupAddon>
                                <InputGroupButton type="button" size="icon-xs" @click="decrementGrade">
                                    <MinusIcon />
                                </InputGroupButton>
                            </InputGroupAddon>
                            <InputGroupInput id="note" v-model="grade" type="number" :min="1" :max="6" :step="0.5"
                                class="text-center font-semibold" />
                            <InputGroupAddon align="inline-end">
                                <InputGroupButton type="button" size="icon-xs" @click="incrementGrade">
                                    <PlusIcon />
                                </InputGroupButton>
                            </InputGroupAddon>
                        </InputGroup>
                        <FieldDescription>De {{ GRADE_MIN.toFixed(1) }} à {{ GRADE_MAX.toFixed(1) }}, par pas
                            de {{ GRADE_STEP.toFixed(1) }}</FieldDescription>
                    </Field>

                    <Field class="flex-1">
                        <FieldLabel for="date-day">Date du test</FieldLabel>
                        <div class="flex gap-2">
                            <Select v-model="dateMonth">
                                <SelectTrigger id="date-month" class="flex-1">
                                    <SelectValue placeholder="Mois" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem v-for="month in MONTHS" :key="month.value" :value="month.value">
                                            {{ month.label }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <Input id="date-day" v-model="dateDay" type="number" :min="1" :max="31"
                                placeholder="Jour" class="w-20" />
                            <Input id="date-year" v-model="dateYear" type="number" :min="1900" :max="2100"
                                placeholder="Année" class="w-24" />
                        </div>
                    </Field>
                </div>

                <Field v-if="!isModuleTest">
                    <FieldLabel>Justificatif</FieldLabel>
                    <div class="flex items-center gap-2 mb-2">
                        <Switch id="is-oral" :model-value="isOral" @update:model-value="switchToOral" />
                        <FieldLabel for="is-oral" class="font-normal">Épreuve orale</FieldLabel>
                    </div>

                    <FieldDescription v-if="isOral">
                        Épreuve orale aucun document requis pour une épreuve orale.
                    </FieldDescription>

                    <div v-else
                        class="flex flex-col items-center justify-center gap-1 rounded-md border border-dashed p-8 text-center"
                        @dragover.prevent @drop="handleDrop">
                        <UploadIcon class="size-5 text-muted-foreground" />
                        <p class="text-sm">
                            Glisser le scan du test ici, ou
                            <button type="button" class="text-primary underline underline-offset-2"
                                @click="fileInput?.click()">
                                parcourir
                            </button>
                        </p>
                        <p class="text-xs text-muted-foreground">PDF uniquement, 10 Mo maximum</p>
                        <p v-if="selectedFile" class="text-xs text-foreground">{{ selectedFile.name }}</p>
                        <input ref="fileInput" type="file" accept="application/pdf" class="hidden"
                            @change="onFileChange" />
                    </div>
                </Field>
            </CardContent>

            <Separator />

            <CardFooter class="justify-start gap-2">
                <Button type="submit">Enregistrer la note</Button>
                <Button as-child type="button" variant="outline">
                    <Link href="/">Annuler</Link>
                </Button>
            </CardFooter>
        </Card>
        </form>
    </div>
</template>
