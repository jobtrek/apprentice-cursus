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
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { GRADE_MAX, GRADE_MIN, GRADE_STEP } from '@/constants/constants';
import { useGradeForm } from '@/composables/useGradeForm';
import { MinusIcon, PlusIcon, UploadIcon } from '@lucide/vue';

const {
    subjects,
    MatureSubjects,
    is_mp,
    is_epsic,
    is_module_test,
    cieModules,
    epsicModules,
    is_oral,
    grade,
    testDate,
    selectedFile,
    fileInput,
    decrementGrade,
    incrementGrade,
    handleDrop,
    switchToOral,
    onFileChange,
} = useGradeForm();
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-4">
        <div>
            <h2 class="text-2xl font-semibold self-start">Ajouter une note</h2>
        </div>
        <Card class="w-160">
            <CardHeader>
                <FieldGroup>
                    <div class="flex justify-end">
                        <Button type="button" variant="outline" size="sm" @click="is_module_test = !is_module_test">
                            <span v-if="is_module_test">Noter une épreuve ECG</span>
                            <span v-else>Noter un module</span>
                        </Button>
                    </div>

                    <Field v-if="!is_module_test">
                        <FieldLabel for="matiere">Matière</FieldLabel>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-sm text-muted-foreground">Matières</span>
                            <Button type="button" variant="outline" size="sm" @click="is_mp = !is_mp">
                                <span v-if="is_mp">Voir les matières de MP</span>
                                <span v-else>Voir les matières de maturité</span>
                            </Button>
                        </div>
                        <Select>
                            <SelectTrigger id="matiere" class="w-full">
                                <SelectValue placeholder="Sélectionner une matière" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectLabel>Matières</SelectLabel>
                                    <SelectItem v-for="subject in is_mp ? MatureSubjects : subjects" :key="subject"
                                        :value="subject">
                                        {{ subject }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </Field>

                    <Field v-else>
                        <FieldLabel for="module">Module</FieldLabel>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-sm text-muted-foreground">Modules</span>
                            <Button type="button" variant="outline" size="sm" @click="is_epsic = !is_epsic">
                                <span v-if="is_epsic">Voir les modules de CIE</span>
                                <span v-else>Voir les modules d'EPSIC</span>
                            </Button>
                        </div>
                        <Select>
                            <SelectTrigger id="module" class="w-full">
                                <SelectValue placeholder="Sélectionner un module" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectLabel>Modules</SelectLabel>
                                    <SelectItem v-for="module in is_epsic ? epsicModules : cieModules"
                                        :key="module.id" :value="String(module.id)">
                                        {{ module.code }} — {{ module.name }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
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
                        <FieldDescription>De {{ GRADE_MIN.toFixed(1) }} à {{ GRADE_MAX.toFixed(1) }}, par pas de {{
                            GRADE_STEP.toFixed(1) }}</FieldDescription>
                    </Field>

                    <Field class="flex-1">
                        <FieldLabel for="date">Date du test</FieldLabel>
                        <Input id="date" v-model="testDate" type="date" />
                    </Field>
                </div>

                <Field v-if="!is_module_test">
                    <FieldLabel>Justificatif</FieldLabel>
                    <Button type="button" variant="outline" class="mb-2" @click="switchToOral">
                        <span v-if="is_oral">Marquer comme épreuve écrite</span>
                        <span v-else>Marquer comme épreuve orale</span>
                    </Button>

                    <FieldDescription v-if="is_oral">
                        Épreuve orale — aucun document requis pour une épreuve orale.
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
                <Button type="button" variant="outline">Annuler</Button>
            </CardFooter>
        </Card>
    </div>
</template>