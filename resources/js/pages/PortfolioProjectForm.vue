<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PlusIcon, Trash2Icon, XIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
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
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSeparator,
    FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Toggle } from '@/components/ui/toggle';
import { usePortfolio } from '@/composables/usePortfolio';
import { usePortfolioForm } from '@/composables/usePortfolioForm';
import portfolio from '@/routes/portfolio';
import { PageContainer, PageHeader } from '@/components/page';

const props = defineProps<{
    projectId?: number;
}>();

const { findProject, deleteProject } = usePortfolio();

const existing = computed(() =>
    props.projectId ? findProject(props.projectId) : undefined,
);

const {
    form,
    skills,
    errors,
    isEditing,
    technologyDraft,
    addTechnology,
    removeTechnology,
    removeLastTechnology,
    toggleSkill,
    hasSkill,
    addScreenshots,
    removeScreenshot,
    submit,
} = usePortfolioForm(existing.value);

const heading = computed(() =>
    isEditing.value ? form.value.title : 'Nouveau projet',
);

const breadcrumbs = [{ label: 'Portfolio', href: portfolio.index() }];

const screenshotInput = ref<HTMLInputElement | null>(null);

function pickScreenshots(): void {
    screenshotInput.value?.click();
}

async function onScreenshotsPicked(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    await addScreenshots(input.files);
    // Remis à zéro pour que réimporter le même fichier redéclenche l'événement.
    input.value = '';
}

function onSubmit(): void {
    if (submit() === null) {
        return;
    }

    router.visit(portfolio.index());
}

function onDelete(): void {
    if (!isEditing.value) {
        return;
    }

    deleteProject(form.value.id);
    router.visit(portfolio.index());
}
</script>

<template>
    <Head :title="isEditing ? 'Modifier un projet' : 'Nouveau projet'" />

    <PageContainer size="sm">
        <PageHeader
            :title="heading || 'Nouveau projet'"
            :breadcrumbs="breadcrumbs"
        />

        <form novalidate @submit.prevent="onSubmit">
            <Card>
                <CardContent>
                    <FieldGroup>
                        <FieldSet>
                            <FieldLegend>Informations générales</FieldLegend>

                            <Field :data-invalid="Boolean(errors.title)">
                                <FieldLabel for="title"
                                    >Titre du projet</FieldLabel
                                >
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    name="title"
                                    required
                                    :aria-invalid="Boolean(errors.title)"
                                />
                                <FieldError :errors="[errors.title]" />
                            </Field>

                            <div class="grid gap-6 sm:grid-cols-2">
                                <Field>
                                    <FieldLabel for="organization">
                                        Entreprise
                                    </FieldLabel>
                                    <Input
                                        id="organization"
                                        v-model="form.organization"
                                        name="organization"
                                    />
                                </Field>
                                <Field>
                                    <FieldLabel for="responsibilities">
                                        Rôle
                                    </FieldLabel>
                                    <Input
                                        id="responsibilities"
                                        v-model="form.responsibilities"
                                        name="responsibilities"
                                    />
                                </Field>
                            </div>

                            <div class="grid gap-6 sm:grid-cols-2">
                                <Field
                                    :data-invalid="Boolean(errors.date_start)"
                                >
                                    <FieldLabel for="date_start">
                                        Date de début
                                    </FieldLabel>
                                    <Input
                                        id="date_start"
                                        v-model="form.date_start"
                                        type="date"
                                        name="date_start"
                                        required
                                        :aria-invalid="
                                            Boolean(errors.date_start)
                                        "
                                    />
                                    <FieldError :errors="[errors.date_start]" />
                                </Field>
                                <Field :data-invalid="Boolean(errors.date_end)">
                                    <FieldLabel for="date_end">
                                        Date de fin
                                    </FieldLabel>
                                    <Input
                                        id="date_end"
                                        v-model="form.date_end"
                                        type="date"
                                        name="date_end"
                                        :aria-invalid="Boolean(errors.date_end)"
                                    />
                                    <FieldError :errors="[errors.date_end]" />
                                </Field>
                            </div>

                            <Field :data-invalid="Boolean(errors.description)">
                                <FieldLabel for="description">
                                    Description
                                </FieldLabel>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    name="description"
                                    rows="4"
                                    required
                                    :aria-invalid="Boolean(errors.description)"
                                />
                                <FieldError :errors="[errors.description]" />
                            </Field>
                        </FieldSet>

                        <FieldSeparator />

                        <FieldSet>
                            <FieldLegend>Technique</FieldLegend>

                            <Field>
                                <FieldLabel for="technology-draft">
                                    Technologies
                                </FieldLabel>
                                <div
                                    class="border-input focus-within:border-ring focus-within:ring-ring/50 dark:bg-input/30 flex min-h-9 flex-wrap items-center gap-1.5 rounded-md border px-2 py-1.5 shadow-xs focus-within:ring-[3px]"
                                >
                                    <Badge
                                        v-for="technology in form.technologies"
                                        :key="technology"
                                        variant="secondary"
                                        class="gap-1 pr-1"
                                    >
                                        {{ technology }}
                                        <button
                                            type="button"
                                            class="text-muted-foreground hover:text-foreground rounded-sm"
                                            :aria-label="`Retirer ${technology}`"
                                            @click="
                                                removeTechnology(technology)
                                            "
                                        >
                                            <XIcon
                                                class="size-3"
                                                aria-hidden="true"
                                            />
                                        </button>
                                    </Badge>
                                    <input
                                        id="technology-draft"
                                        v-model="technologyDraft"
                                        class="placeholder:text-muted-foreground min-w-40 flex-1 bg-transparent text-sm outline-none"
                                        placeholder="Ajouter une technologie…"
                                        @keydown.enter.prevent="addTechnology"
                                        @keydown.,.prevent="addTechnology"
                                        @keydown.backspace="
                                            removeLastTechnology
                                        "
                                        @blur="addTechnology"
                                    />
                                </div>
                                <FieldDescription>
                                    Validez avec Entrée ou une virgule.
                                </FieldDescription>
                            </Field>

                            <div class="grid gap-6 sm:grid-cols-2">
                                <Field>
                                    <FieldLabel for="demo_path">
                                        Lien de démonstration
                                    </FieldLabel>
                                    <Input
                                        id="demo_path"
                                        v-model="form.demo_path"
                                        type="url"
                                        name="demo_path"
                                        placeholder="https://"
                                    />
                                </Field>
                                <Field>
                                    <FieldLabel for="repository_url">
                                        Lien du code source
                                    </FieldLabel>
                                    <Input
                                        id="repository_url"
                                        v-model="form.repository_url"
                                        type="url"
                                        name="repository_url"
                                        placeholder="https://"
                                    />
                                </Field>
                            </div>
                        </FieldSet>

                        <FieldSeparator />

                        <FieldSet>
                            <FieldLegend>Captures d'écran</FieldLegend>

                            <Field :data-invalid="Boolean(errors.screenshots)">
                                <div class="flex flex-wrap gap-3">
                                    <div
                                        v-for="(
                                            screenshot, index
                                        ) in form.screenshots"
                                        :key="index"
                                        class="relative"
                                    >
                                        <img
                                            :src="screenshot"
                                            :alt="`Capture d'écran ${index + 1}`"
                                            class="bg-muted size-24 rounded-md object-cover"
                                        />
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            size="icon-xs"
                                            class="absolute -top-2 -right-2 rounded-full border shadow-xs"
                                            :aria-label="`Retirer la capture ${index + 1}`"
                                            @click="removeScreenshot(index)"
                                        >
                                            <XIcon aria-hidden="true" />
                                        </Button>
                                    </div>

                                    <button
                                        type="button"
                                        class="border-input text-muted-foreground hover:bg-muted/50 hover:text-foreground flex size-24 items-center justify-center rounded-md border border-dashed transition-colors"
                                        aria-label="Ajouter une capture d'écran"
                                        data-test="add-screenshot-button"
                                        @click="pickScreenshots"
                                    >
                                        <PlusIcon
                                            class="size-5"
                                            aria-hidden="true"
                                        />
                                    </button>

                                    <input
                                        ref="screenshotInput"
                                        type="file"
                                        accept="image/*"
                                        multiple
                                        class="hidden"
                                        data-test="screenshot-input"
                                        @change="onScreenshotsPicked"
                                    />
                                </div>
                                <FieldError :errors="[errors.screenshots]" />
                                <FieldDescription>
                                    Images uniquement, 5 Mo maximum par fichier.
                                </FieldDescription>
                            </Field>
                        </FieldSet>

                        <FieldSeparator />

                        <FieldSet>
                            <FieldLegend>Compétences démontrées</FieldLegend>

                            <div class="flex flex-wrap gap-2">
                                <Toggle
                                    v-for="skill in skills"
                                    :key="skill.id"
                                    variant="outline"
                                    size="sm"
                                    class="data-[state=on]:border-primary data-[state=on]:bg-primary/10 data-[state=on]:text-primary"
                                    :model-value="hasSkill(skill.id)"
                                    @update:model-value="toggleSkill(skill.id)"
                                >
                                    {{ skill.name }}
                                </Toggle>
                            </div>
                        </FieldSet>
                    </FieldGroup>
                </CardContent>

                <CardFooter class="flex-wrap gap-2 border-t">
                    <Button type="submit" data-test="save-project-button">
                        Enregistrer le projet
                    </Button>
                    <Button as-child type="button" variant="outline">
                        <Link :href="portfolio.index()">Annuler</Link>
                    </Button>
                    <AlertDialog v-if="isEditing">
                        <AlertDialogTrigger as-child>
                            <Button
                                type="button"
                                variant="ghost"
                                class="text-destructive hover:bg-destructive/10 hover:text-destructive ml-auto"
                            >
                                <Trash2Icon aria-hidden="true" />
                                Supprimer
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>
                                    Supprimer « {{ form.title }} » ?
                                </AlertDialogTitle>
                                <AlertDialogDescription>
                                    Cette action est irréversible. Le projet
                                    sera retiré de votre portfolio.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Annuler</AlertDialogCancel>
                                <AlertDialogAction
                                    :class="
                                        buttonVariants({
                                            variant: 'destructive',
                                        })
                                    "
                                    @click="onDelete"
                                >
                                    Supprimer
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </CardFooter>
            </Card>
        </form>
    </PageContainer>
</template>
