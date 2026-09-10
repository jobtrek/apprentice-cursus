<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeftIcon, ExternalLinkIcon, FileTextIcon } from '@lucide/vue';
import {
    formatPeriod,
    parseTechnologies,
    usePortfolio,
} from '@/composables/usePortfolio';
import { Button } from '@/components/ui/button';
import portfolio from '@/routes/portfolio';

const { projects, owner, skillNames } = usePortfolio();

/**
 * L'export passe par la boîte d'impression du navigateur ("Enregistrer au
 * format PDF"). À remplacer par la génération serveur si l'équipe veut un
 * rendu identique quel que soit le navigateur.
 */
function exportToPdf(): void {
    window.print();
}
</script>

<template>
    <Head title="Aperçu du portfolio" />

    <div class="bg-muted/40 min-h-svh">
        <header
            class="bg-background sticky top-0 z-10 flex items-center justify-between gap-4 border-b px-6 py-3 print:hidden"
        >
            <Link
                :href="portfolio.index()"
                class="text-muted-foreground hover:text-foreground flex items-center gap-1 text-sm"
            >
                <ChevronLeftIcon class="size-4" aria-hidden="true" />
                Aperçu du portfolio
            </Link>

            <Button data-test="export-pdf-button" @click="exportToPdf">
                <FileTextIcon aria-hidden="true" />
                Exporter en PDF
            </Button>
        </header>

        <main
            class="mx-auto my-10 max-w-3xl bg-white px-12 py-12 text-neutral-900 shadow-sm print:my-0 print:max-w-none print:px-0 print:shadow-none"
        >
            <div class="space-y-1">
                <h1 class="text-2xl font-bold">{{ owner.name }}</h1>
                <p class="text-sm text-neutral-500">
                    Portfolio de projets — {{ owner.track }} ·
                    {{ owner.promotion }}
                </p>
            </div>

            <p
                v-if="projects.length === 0"
                class="mt-10 border-t pt-10 text-sm text-neutral-500"
            >
                Aucun projet à afficher pour l'instant. Ajoutez un projet depuis
                le portfolio pour le voir apparaître ici.
            </p>

            <article
                v-for="project in projects"
                :key="project.id"
                class="mt-8 break-inside-avoid border-t border-neutral-200 pt-8"
            >
                <h2 class="font-semibold">{{ project.title }}</h2>

                <p class="mt-1 text-sm text-neutral-500">
                    <template v-if="project.organization">
                        {{ project.organization }} ·
                    </template>
                    {{ formatPeriod(project.date_start, project.date_end) }}
                    <template v-if="project.responsibilities">
                        · {{ project.responsibilities }}
                    </template>
                </p>

                <p class="mt-3 text-sm leading-relaxed">
                    {{ project.description }}
                </p>

                <ul class="mt-3 flex flex-wrap gap-1.5">
                    <li
                        v-for="technology in parseTechnologies(
                            project.technologies,
                        )"
                        :key="technology"
                        class="rounded border border-neutral-200 px-2 py-0.5 text-xs"
                    >
                        {{ technology }}
                    </li>
                </ul>

                <div
                    v-if="project.demo_path || project.repository_url"
                    class="mt-3 flex flex-wrap gap-4 text-sm"
                >
                    <a
                        v-if="project.demo_path"
                        :href="project.demo_path"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-1.5 underline-offset-4 hover:underline"
                    >
                        <ExternalLinkIcon class="size-3.5" aria-hidden="true" />
                        Démonstration
                    </a>
                    <a
                        v-if="project.repository_url"
                        :href="project.repository_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-1.5 underline-offset-4 hover:underline"
                    >
                        <ExternalLinkIcon class="size-3.5" aria-hidden="true" />
                        Code source
                    </a>
                </div>

                <div
                    v-if="project.screenshots.length > 0"
                    class="mt-4 flex flex-wrap gap-3"
                >
                    <img
                        v-for="(screenshot, index) in project.screenshots"
                        :key="index"
                        :src="screenshot"
                        :alt="`${project.title} — capture ${index + 1}`"
                        class="h-20 w-32 rounded bg-neutral-100 object-cover"
                    />
                </div>

                <ul class="mt-4 flex flex-wrap gap-1.5">
                    <li
                        v-for="name in skillNames(project)"
                        :key="name"
                        class="rounded border border-blue-200 bg-blue-50 px-2 py-0.5 text-xs text-blue-900"
                    >
                        {{ name }}
                    </li>
                </ul>
            </article>
        </main>
    </div>
</template>
