<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ImageIcon, PlusIcon, Trash2Icon, XIcon } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { usePortfolio } from '@/composables/usePortfolio';
import { usePortfolioForm } from '@/composables/usePortfolioForm';
import portfolio from '@/routes/portfolio';

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
    addScreenshot,
    removeScreenshot,
    submit,
} = usePortfolioForm(existing.value);

const heading = computed(() =>
    isEditing.value ? form.value.title : 'Nouveau projet',
);

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

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
        <div class="space-y-1">
            <Link
                :href="portfolio.index()"
                class="text-muted-foreground hover:text-foreground text-sm"
            >
                Portfolio
            </Link>
            <h1 class="text-xl font-semibold">
                {{ heading || 'Nouveau projet' }}
            </h1>
        </div>

        <Card>
            <CardContent>
                <form
                    class="flex flex-col gap-8"
                    novalidate
                    @submit.prevent="onSubmit"
                >
                    <fieldset class="space-y-4">
                        <legend
                            class="text-muted-foreground mb-4 text-xs font-medium tracking-wider uppercase"
                        >
                            Informations générales
                        </legend>

                        <div class="grid gap-2">
                            <Label for="title">Titre du projet</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                name="title"
                                required
                                :aria-invalid="Boolean(errors.title)"
                            />
                            <InputError :message="errors.title" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="organization">Entreprise</Label>
                                <Input
                                    id="organization"
                                    v-model="form.organization"
                                    name="organization"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="responsibilities">Rôle</Label>
                                <Input
                                    id="responsibilities"
                                    v-model="form.responsibilities"
                                    name="responsibilities"
                                />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="date_start">Date de début</Label>
                                <Input
                                    id="date_start"
                                    v-model="form.date_start"
                                    type="date"
                                    name="date_start"
                                    required
                                    :aria-invalid="Boolean(errors.date_start)"
                                />
                                <InputError :message="errors.date_start" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="date_end">Date de fin</Label>
                                <Input
                                    id="date_end"
                                    v-model="form.date_end"
                                    type="date"
                                    name="date_end"
                                    :aria-invalid="Boolean(errors.date_end)"
                                />
                                <InputError :message="errors.date_end" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                name="description"
                                rows="4"
                                required
                                :aria-invalid="Boolean(errors.description)"
                            />
                            <InputError :message="errors.description" />
                        </div>
                    </fieldset>

                    <Separator />

                    <fieldset class="space-y-4">
                        <legend
                            class="text-muted-foreground mb-4 text-xs font-medium tracking-wider uppercase"
                        >
                            Technique
                        </legend>

                        <div class="grid gap-2">
                            <Label for="technology-draft">Technologies</Label>
                            <div
                                class="border-input focus-within:border-ring focus-within:ring-ring/50 flex flex-wrap items-center gap-1.5 rounded-md border px-2 py-1.5 focus-within:ring-[3px]"
                            >
                                <span
                                    v-for="technology in form.technologies"
                                    :key="technology"
                                    class="bg-muted flex items-center gap-1 rounded px-2 py-0.5 text-xs"
                                >
                                    {{ technology }}
                                    <button
                                        type="button"
                                        class="text-muted-foreground hover:text-foreground"
                                        :aria-label="`Retirer ${technology}`"
                                        @click="removeTechnology(technology)"
                                    >
                                        <XIcon
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                    </button>
                                </span>
                                <input
                                    id="technology-draft"
                                    v-model="technologyDraft"
                                    class="min-w-40 flex-1 bg-transparent text-sm outline-none"
                                    placeholder="Ajouter une technologie..."
                                    @keydown.enter.prevent="addTechnology"
                                    @keydown.,.prevent="addTechnology"
                                    @keydown.backspace="removeLastTechnology"
                                    @blur="addTechnology"
                                />
                            </div>
                            <p class="text-muted-foreground text-xs">
                                Validez avec Entrée ou une virgule.
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="demo_path">
                                    Lien de démonstration
                                </Label>
                                <Input
                                    id="demo_path"
                                    v-model="form.demo_path"
                                    type="url"
                                    name="demo_path"
                                    placeholder="https://"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="repository_url">
                                    Lien du code source
                                </Label>
                                <Input
                                    id="repository_url"
                                    v-model="form.repository_url"
                                    type="url"
                                    name="repository_url"
                                    placeholder="https://"
                                />
                            </div>
                        </div>
                    </fieldset>

                    <Separator />

                    <fieldset class="space-y-4">
                        <legend
                            class="text-muted-foreground mb-4 text-xs font-medium tracking-wider uppercase"
                        >
                            Captures d'écran
                        </legend>

                        <div class="flex flex-wrap gap-3">
                            <div
                                v-for="(screenshot, index) in form.screenshots"
                                :key="index"
                                class="relative"
                            >
                                <div
                                    class="bg-muted text-muted-foreground flex size-24 items-center justify-center rounded"
                                >
                                    <ImageIcon
                                        class="size-5"
                                        aria-hidden="true"
                                    />
                                </div>
                                <button
                                    type="button"
                                    class="bg-foreground text-background absolute -top-2 -right-2 flex size-5 items-center justify-center rounded-full"
                                    :aria-label="`Retirer la capture ${index + 1}`"
                                    @click="removeScreenshot(index)"
                                >
                                    <XIcon class="size-3" aria-hidden="true" />
                                </button>
                            </div>

                            <button
                                type="button"
                                class="border-input text-muted-foreground hover:bg-accent flex size-24 items-center justify-center rounded border border-dashed"
                                aria-label="Ajouter une capture d'écran"
                                @click="addScreenshot"
                            >
                                <PlusIcon class="size-5" aria-hidden="true" />
                            </button>
                        </div>
                        <p class="text-muted-foreground text-xs">
                            L'envoi de fichiers sera branché quand la route de
                            stockage sera disponible.
                        </p>
                    </fieldset>

                    <Separator />

                    <fieldset class="space-y-4">
                        <legend
                            class="text-muted-foreground mb-4 text-xs font-medium tracking-wider uppercase"
                        >
                            Compétences démontrées
                        </legend>

                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="skill in skills"
                                :key="skill.id"
                                type="button"
                                class="rounded border px-3 py-1.5 text-sm transition-colors"
                                :class="
                                    hasSkill(skill.id)
                                        ? 'border-primary bg-primary/10 text-primary'
                                        : 'border-input hover:bg-accent'
                                "
                                :aria-pressed="hasSkill(skill.id)"
                                @click="toggleSkill(skill.id)"
                            >
                                {{ skill.name }}
                            </button>
                        </div>
                    </fieldset>

                    <Separator />

                    <div class="flex flex-wrap items-center gap-3">
                        <Button type="submit" data-test="save-project-button">
                            Enregistrer le projet
                        </Button>
                        <Button as-child type="button" variant="outline">
                            <Link :href="portfolio.index()">Annuler</Link>
                        </Button>
                        <Button
                            v-if="isEditing"
                            type="button"
                            variant="ghost"
                            class="text-destructive hover:text-destructive ml-auto"
                            @click="onDelete"
                        >
                            <Trash2Icon aria-hidden="true" />
                            Supprimer
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
