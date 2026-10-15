<script setup lang="ts">
import { CheckIcon, GraduationCapIcon } from '@lucide/vue';
import { computed } from 'vue';
import IconTile from '@/components/IconTile.vue';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { fmt, type Gradebook, type Status, status } from '@/lib/gradebook';
import { cn } from '@/lib/utils';

/**
 * Parcours de formation : les 8 semestres en étapes, façon Stepper de reui.io
 * (https://reui.io/components/stepper). Un semestre passé est « terminé », le
 * semestre courant est mis en avant, chaque étape notée affiche sa moyenne.
 */
const props = defineProps<{
    gradebook: Gradebook;
    profile: { track: string | null; variant: 'standard' | 'mp' } | null;
    /**
     * Le semestre courant est déduit du dernier semestre noté (vue coach),
     * faute de date de début en base : le sous-titre le précise.
     */
    derived?: boolean;
}>();

const STATUS_TEXT: Record<Status, string> = {
    good: 'text-success',
    warn: 'text-warning',
    bad: 'text-destructive',
};

type Step = {
    semester: number;
    state: 'done' | 'current' | 'upcoming';
    average: number | null;
};

/** 8 semestres en IT, 6 en EC. */
const semesters = computed(() => props.gradebook.semesterCount);
const currentSemester = computed(() => props.gradebook.currentSemester);

const steps = computed<Step[]>(() =>
    Array.from({ length: semesters.value }, (_, i) => {
        const semester = i + 1;

        return {
            semester,
            state:
                semester < currentSemester.value
                    ? 'done'
                    : semester === currentSemester.value
                      ? 'current'
                      : 'upcoming',
            average: props.gradebook.semesterValue(semester),
        };
    }),
);

/** Année d'apprentissage en cours, à partir de 1. */
const year = computed(() => Math.ceil(currentSemester.value / 2));
</script>

<template>
    <Card
        class="gap-0 py-0 shadow-xs"
        role="region"
        aria-labelledby="training-path-title"
    >
        <div
            class="flex flex-col gap-4 border-b p-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-3">
                <IconTile>
                    <GraduationCapIcon />
                </IconTile>
                <div class="flex flex-col gap-0.5">
                    <h2 id="training-path-title" class="font-semibold">
                        Parcours de formation
                    </h2>
                    <p class="text-muted-foreground text-sm">
                        {{ year }}<sup>{{ year === 1 ? 're' : 'e' }}</sup> année
                        ·
                        {{
                            derived
                                ? `dernier semestre noté : ${currentSemester}`
                                : `semestre ${currentSemester}`
                        }}
                        sur
                        {{ semesters }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Badge v-if="profile?.track" variant="outline">
                    Filière {{ profile.track }}
                </Badge>
                <Badge v-if="profile" variant="outline">
                    {{
                        profile.variant === 'mp'
                            ? 'Maturité professionnelle'
                            : 'Variante standard'
                    }}
                </Badge>
            </div>
        </div>

        <!-- Défile horizontalement sur petit écran plutôt que de s'écraser. -->
        <div class="overflow-x-auto p-5">
            <ol class="flex min-w-160" aria-label="Semestres du cursus">
                <li
                    v-for="(step, index) in steps"
                    :key="step.semester"
                    class="flex flex-1 flex-col items-center gap-2"
                    :aria-current="
                        step.state === 'current' ? 'step' : undefined
                    "
                >
                    <div
                        class="relative flex w-full items-center justify-center"
                    >
                        <!-- Liaison vers l'étape précédente. -->
                        <span
                            v-if="index > 0"
                            class="absolute top-1/2 right-1/2 left-[-50%] h-0.5 -translate-y-1/2"
                            :class="
                                step.state === 'upcoming'
                                    ? 'bg-border'
                                    : 'bg-primary'
                            "
                            aria-hidden="true"
                        />
                        <span
                            :class="
                                cn(
                                    'relative z-10 flex size-9 items-center justify-center rounded-full border-2 text-sm font-semibold tabular-nums',
                                    step.state === 'done' &&
                                        'border-primary bg-primary text-primary-foreground',
                                    step.state === 'current' &&
                                        'border-primary bg-background text-primary ring-primary/15 ring-4',
                                    step.state === 'upcoming' &&
                                        'border-border bg-background text-muted-foreground',
                                )
                            "
                        >
                            <CheckIcon
                                v-if="step.state === 'done'"
                                class="size-4"
                                aria-hidden="true"
                            />
                            <template v-else>{{ step.semester }}</template>
                            <span class="sr-only">
                                Semestre {{ step.semester }},
                                {{
                                    step.state === 'done'
                                        ? 'terminé'
                                        : step.state === 'current'
                                          ? 'en cours'
                                          : 'à venir'
                                }}
                            </span>
                        </span>
                    </div>

                    <div class="flex flex-col items-center gap-0.5 text-center">
                        <span
                            class="text-xs font-medium"
                            :class="
                                step.state === 'upcoming'
                                    ? 'text-muted-foreground'
                                    : 'text-foreground'
                            "
                        >
                            S{{ step.semester }}
                        </span>
                        <span
                            v-if="step.average !== null"
                            class="text-sm font-semibold tabular-nums"
                            :class="STATUS_TEXT[status(step.average)[0]]"
                            :title="`Moyenne du semestre ${step.semester}`"
                        >
                            {{ fmt(step.average) }}
                        </span>
                        <span v-else class="text-muted-foreground text-sm">
                            —
                        </span>
                    </div>
                </li>
            </ol>

            <!-- Années : une étiquette sous chaque paire de semestres. -->
            <div
                class="text-muted-foreground mt-3 grid min-w-160 gap-2 text-center text-xs"
                :style="{
                    gridTemplateColumns: `repeat(${semesters / 2}, minmax(0, 1fr))`,
                }"
                aria-hidden="true"
            >
                <span
                    v-for="n in semesters / 2"
                    :key="n"
                    class="border-t pt-2"
                    :class="{
                        'text-primary border-primary font-medium': n === year,
                    }"
                >
                    Année {{ n }}
                </span>
            </div>
        </div>
    </Card>
</template>
