import { computed, ref } from 'vue';
import portfolioData from '@/data/portfolio.json';
import type {
    PortfolioOwner,
    PortfolioProject,
    Skill,
} from '@/types/portfolio';

/**
 * État partagé du portfolio. Tant que le backend n'expose pas les projets via
 * Inertia, les données viennent de `@/data/portfolio.json` et les mutations
 * restent en mémoire : elles sont perdues au rechargement complet de la page.
 */
const projects = ref<PortfolioProject[]>(
    structuredClone(portfolioData.projects) as PortfolioProject[],
);
const skills = ref<Skill[]>(portfolioData.skills as Skill[]);
const owner = ref<PortfolioOwner>(portfolioData.owner as PortfolioOwner);

const MONTHS = [
    'Janvier',
    'Février',
    'Mars',
    'Avril',
    'Mai',
    'Juin',
    'Juillet',
    'Août',
    'Septembre',
    'Octobre',
    'Novembre',
    'Décembre',
];

/** `2025-03-01` -> `Mars 2025`. */
export function formatMonthYear(date: string | null): string {
    if (!date) {
        return '';
    }

    const [year, month] = date.split('-');
    const monthName = MONTHS[Number(month) - 1];

    return monthName ? `${monthName} ${year}` : year;
}

/** `Mars 2025 – Juin 2025`, ou `Janvier 2026 – en cours` si pas de fin. */
export function formatPeriod(start: string, end: string | null): string {
    return `${formatMonthYear(start)} – ${
        end ? formatMonthYear(end) : 'en cours'
    }`;
}

/** `Vue.js, Laravel` -> `['Vue.js', 'Laravel']`. */
export function parseTechnologies(technologies: string | null): string[] {
    if (!technologies) {
        return [];
    }

    return technologies
        .split(',')
        .map((technology) => technology.trim())
        .filter((technology) => technology.length > 0);
}

export function usePortfolio() {
    const skillsById = computed(
        () => new Map(skills.value.map((skill) => [skill.id, skill])),
    );

    function findProject(id: number): PortfolioProject | undefined {
        return projects.value.find((project) => project.id === id);
    }

    function skillNames(project: PortfolioProject): string[] {
        return project.skill_ids
            .map((id) => skillsById.value.get(id)?.name)
            .filter((name): name is string => name !== undefined);
    }

    /** Crée le projet s'il n'a pas d'id, le met à jour sinon. */
    function saveProject(project: PortfolioProject): PortfolioProject {
        const index = projects.value.findIndex(
            (candidate) => candidate.id === project.id,
        );

        if (index === -1) {
            const nextId =
                projects.value.reduce(
                    (max, candidate) => Math.max(max, candidate.id),
                    0,
                ) + 1;
            const created = { ...project, id: nextId };
            projects.value.push(created);

            return created;
        }

        projects.value[index] = { ...project };

        return projects.value[index];
    }

    function deleteProject(id: number): void {
        projects.value = projects.value.filter((project) => project.id !== id);
    }

    /** Déplace un projet dans la liste, en bornant la position d'arrivée. */
    function moveProject(from: number, to: number): void {
        if (to < 0 || to >= projects.value.length || from === to) {
            return;
        }

        const reordered = [...projects.value];
        const [moved] = reordered.splice(from, 1);
        reordered.splice(to, 0, moved);
        projects.value = reordered;
    }

    return {
        projects,
        skills,
        owner,
        findProject,
        skillNames,
        saveProject,
        deleteProject,
        moveProject,
    };
}
