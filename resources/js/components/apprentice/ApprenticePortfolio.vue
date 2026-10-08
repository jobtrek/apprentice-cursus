<script setup lang="ts">
import { useRemember } from '@inertiajs/vue3';
import {
    ExternalLinkIcon,
    FolderOpenIcon,
    GitCommitVerticalIcon,
    LayoutGridIcon,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import FilterSelect from '@/components/FilterSelect.vue';
import PortfolioStats from '@/components/portfolio/PortfolioStats.vue';
import ProjectTimeline from '@/components/portfolio/ProjectTimeline.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { formatPeriod, skillNames } from '@/composables/usePortfolio';
import type { SupervisedPortfolio } from '@/types/portfolio';

const props = defineProps<{
    portfolio: SupervisedPortfolio;
}>();

const ALL = 'all';
/** Préfixe les technologies (saisie libre) pour qu'aucune ne puisse valoir ALL. */
const TECH = 'tech:';

const search = ref('');
const technology = ref(ALL);
const skill = ref(ALL);

/** Valeurs réellement utilisées par les projets, triées. */
const technologyOptions = computed(() => [
    { value: ALL, label: 'Toutes les technologies' },
    ...[
        ...new Set(
            props.portfolio.projects.flatMap(
                ({ technologies }) => technologies,
            ),
        ),
    ]
        .sort((a, b) => a.localeCompare(b, 'fr'))
        .map((name) => ({ value: TECH + name, label: name })),
]);

const skillOptions = computed(() => {
    const used = new Set(
        props.portfolio.projects.flatMap(({ skill_ids }) => skill_ids),
    );

    return [
        { value: ALL, label: 'Toutes les compétences' },
        ...props.portfolio.skills
            .filter(({ id }) => used.has(id))
            .map(({ id, name }) => ({ value: String(id), label: name })),
    ];
});

const isFiltered = computed(
    () =>
        search.value.trim() !== '' ||
        technology.value !== ALL ||
        skill.value !== ALL,
);

const projects = computed(() => {
    const query = search.value.trim().toLocaleLowerCase('fr');

    return props.portfolio.projects.filter(
        (project) =>
            (query === '' ||
                [
                    project.title,
                    project.organization,
                    project.responsibilities,
                    project.description,
                ].some((text) =>
                    text?.toLocaleLowerCase('fr').includes(query),
                )) &&
            (technology.value === ALL ||
                project.technologies.includes(
                    technology.value.slice(TECH.length),
                )) &&
            (skill.value === ALL ||
                project.skill_ids.includes(Number(skill.value))),
    );
});

/** Cartes détaillées, ou chronologie triée par date de début. */
type PortfolioView = 'cards' | 'timeline';
const view = useRemember(
    ref<PortfolioView>('cards'),
    'ApprenticePortfolio:view',
);

const selectView = (value: unknown): void => {
    // Un ToggleGroup « single » renvoie une valeur vide si on reclique l'actif.
    if (value === 'cards' || value === 'timeline') {
        view.value = value;
    }
};

function reset(): void {
    search.value = '';
    technology.value = ALL;
    skill.value = ALL;
}
</script>

<template>
    <!-- Lecture seule : le coach et le formateur ne modifient jamais le portfolio. -->
    <PortfolioStats
        v-if="portfolio.projects.length > 0"
        :projects="portfolio.projects"
    />

    <div
        v-if="portfolio.projects.length > 0"
        class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center"
    >
        <SearchInput
            v-model="search"
            placeholder="Rechercher un projet"
            class="sm:max-w-xs"
        />
        <FilterSelect
            v-if="technologyOptions.length > 2"
            v-model="technology"
            :options="technologyOptions"
            :neutral="ALL"
            label="Filtrer par technologie"
        />
        <FilterSelect
            v-if="skillOptions.length > 2"
            v-model="skill"
            :options="skillOptions"
            :neutral="ALL"
            label="Filtrer par compétence"
        />
        <Button
            v-if="isFiltered"
            variant="link"
            size="sm"
            class="h-auto px-0"
            @click="reset"
        >
            {{ projects.length }} sur {{ portfolio.projects.length }} · Effacer
            les filtres
        </Button>
        <ToggleGroup
            :model-value="view"
            type="single"
            variant="outline"
            size="sm"
            class="sm:ml-auto"
            aria-label="Affichage des projets"
            @update:model-value="selectView"
        >
            <ToggleGroupItem value="cards" class="px-3">
                <LayoutGridIcon aria-hidden="true" />
                Cartes
            </ToggleGroupItem>
            <ToggleGroupItem value="timeline" class="px-3">
                <GitCommitVerticalIcon aria-hidden="true" />
                Chronologie
            </ToggleGroupItem>
        </ToggleGroup>
    </div>

    <p
        v-if="portfolio.projects.length > 0 && projects.length === 0"
        class="text-muted-foreground py-8 text-center text-sm"
    >
        Aucun projet ne correspond à ces critères.
    </p>

    <ProjectTimeline
        v-if="projects.length > 0 && view === 'timeline'"
        :projects="projects"
        readonly
    />

    <div v-else-if="projects.length > 0" class="grid gap-4 lg:grid-cols-2">
        <Card
            v-for="project in projects"
            :key="project.id"
            class="gap-4 shadow-xs"
        >
            <CardHeader>
                <CardTitle>{{ project.title }}</CardTitle>
                <CardDescription>
                    <template v-if="project.organization">
                        {{ project.organization }} ·
                    </template>
                    {{ formatPeriod(project.date_start, project.date_end) }}
                    <template v-if="project.responsibilities">
                        · {{ project.responsibilities }}
                    </template>
                </CardDescription>
            </CardHeader>

            <CardContent class="flex flex-col gap-4">
                <p class="text-sm/relaxed whitespace-pre-line">
                    {{ project.description }}
                </p>

                <div
                    v-if="project.screenshots.length > 0"
                    class="flex gap-2 overflow-x-auto"
                >
                    <a
                        v-for="(screenshot, index) in project.screenshots"
                        :key="screenshot.id"
                        :href="screenshot.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="shrink-0"
                    >
                        <img
                            :src="screenshot.url"
                            :alt="`${project.title} — capture ${index + 1}`"
                            loading="lazy"
                            class="bg-muted h-20 w-32 rounded-md object-cover"
                        />
                    </a>
                </div>

                <div
                    v-if="project.technologies.length > 0"
                    class="flex flex-wrap gap-1.5"
                >
                    <Badge
                        v-for="technology in project.technologies"
                        :key="technology"
                        variant="outline"
                    >
                        {{ technology }}
                    </Badge>
                </div>

                <div
                    v-if="skillNames(project, portfolio.skills).length > 0"
                    class="flex flex-wrap gap-1.5"
                >
                    <Badge
                        v-for="name in skillNames(project, portfolio.skills)"
                        :key="name"
                        variant="secondary"
                    >
                        {{ name }}
                    </Badge>
                </div>

                <div
                    v-if="project.demo_path || project.repository_url"
                    class="flex flex-wrap gap-4 text-sm"
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
            </CardContent>
        </Card>
    </div>

    <Empty v-else-if="portfolio.projects.length === 0" class="border">
        <EmptyHeader>
            <EmptyMedia variant="icon">
                <FolderOpenIcon />
            </EmptyMedia>
            <EmptyTitle>Aucun projet pour l'instant</EmptyTitle>
            <EmptyDescription>
                Les projets ajoutés par l'apprenti·e à son portfolio
                apparaîtront ici.
            </EmptyDescription>
        </EmptyHeader>
    </Empty>
</template>
