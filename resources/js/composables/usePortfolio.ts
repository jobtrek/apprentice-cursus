import type { PortfolioProject, Skill } from '@/types/portfolio';

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

/** Noms des compétences du projet, dans l'ordre du catalogue `skills`. */
export function skillNames(
    project: PortfolioProject,
    skills: Skill[],
): string[] {
    return skills
        .filter((skill) => project.skill_ids.includes(skill.id))
        .map((skill) => skill.name);
}
