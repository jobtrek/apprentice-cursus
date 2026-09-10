import { computed, ref } from 'vue';
import { usePortfolio } from '@/composables/usePortfolio';
import { parseTechnologies } from '@/composables/usePortfolio';
import type { PortfolioProject } from '@/types/portfolio';

type ProjectForm = Omit<
    PortfolioProject,
    | 'organization'
    | 'responsibilities'
    | 'technologies'
    | 'repository_url'
    | 'demo_path'
    | 'date_end'
> & {
    organization: string;
    responsibilities: string;
    technologies: string[];
    repository_url: string;
    demo_path: string;
    date_end: string;
};

function blankForm(): ProjectForm {
    return {
        id: 0,
        title: '',
        organization: '',
        description: '',
        responsibilities: '',
        technologies: [],
        repository_url: '',
        demo_path: '',
        date_start: '',
        date_end: '',
        screenshots: [],
        skill_ids: [],
    };
}

function toForm(project: PortfolioProject): ProjectForm {
    return {
        ...project,
        organization: project.organization ?? '',
        responsibilities: project.responsibilities ?? '',
        repository_url: project.repository_url ?? '',
        demo_path: project.demo_path ?? '',
        date_end: project.date_end ?? '',
        technologies: parseTechnologies(project.technologies),
        screenshots: [...project.screenshots],
        skill_ids: [...project.skill_ids],
    };
}

/** Renvoie null si la valeur est vide, pour coller aux colonnes nullable. */
function nullIfBlank(value: string): string | null {
    const trimmed = value.trim();

    return trimmed.length > 0 ? trimmed : null;
}

export function usePortfolioForm(existing?: PortfolioProject) {
    const { skills, saveProject } = usePortfolio();

    const form = ref<ProjectForm>(existing ? toForm(existing) : blankForm());
    const technologyDraft = ref('');
    const errors = ref<Record<string, string>>({});

    const isEditing = computed(() => form.value.id !== 0);

    function addTechnology(): void {
        const technology = technologyDraft.value.trim();

        if (
            technology.length === 0 ||
            form.value.technologies.includes(technology)
        ) {
            technologyDraft.value = '';

            return;
        }

        form.value.technologies.push(technology);
        technologyDraft.value = '';
    }

    function removeTechnology(technology: string): void {
        form.value.technologies = form.value.technologies.filter(
            (candidate) => candidate !== technology,
        );
    }

    /** Retire la dernière techno quand on recule dans un champ déjà vide. */
    function removeLastTechnology(): void {
        if (technologyDraft.value.length === 0) {
            form.value.technologies.pop();
        }
    }

    function toggleSkill(id: number): void {
        const index = form.value.skill_ids.indexOf(id);

        if (index === -1) {
            form.value.skill_ids.push(id);

            return;
        }

        form.value.skill_ids.splice(index, 1);
    }

    function hasSkill(id: number): boolean {
        return form.value.skill_ids.includes(id);
    }

    function addScreenshot(): void {
        form.value.screenshots.push('');
    }

    function removeScreenshot(index: number): void {
        form.value.screenshots.splice(index, 1);
    }

    /**
     * Validation côté client uniquement : elle sera doublée par une Form
     * Request dès que le backend exposera la route.
     */
    function validate(): boolean {
        const found: Record<string, string> = {};

        if (form.value.title.trim().length === 0) {
            found.title = 'Le titre du projet est obligatoire.';
        }

        if (form.value.description.trim().length === 0) {
            found.description = 'La description est obligatoire.';
        }

        if (form.value.date_start.length === 0) {
            found.date_start = 'La date de début est obligatoire.';
        }

        if (
            form.value.date_end.length > 0 &&
            form.value.date_end < form.value.date_start
        ) {
            found.date_end = 'La date de fin doit suivre la date de début.';
        }

        errors.value = found;

        return Object.keys(found).length === 0;
    }

    function submit(): PortfolioProject | null {
        if (!validate()) {
            return null;
        }

        return saveProject({
            id: form.value.id,
            title: form.value.title.trim(),
            organization: nullIfBlank(form.value.organization),
            description: form.value.description.trim(),
            responsibilities: nullIfBlank(form.value.responsibilities),
            technologies:
                form.value.technologies.length > 0
                    ? form.value.technologies.join(', ')
                    : null,
            repository_url: nullIfBlank(form.value.repository_url),
            demo_path: nullIfBlank(form.value.demo_path),
            date_start: form.value.date_start,
            date_end: nullIfBlank(form.value.date_end),
            screenshots: form.value.screenshots,
            skill_ids: form.value.skill_ids,
        });
    }

    return {
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
    };
}
