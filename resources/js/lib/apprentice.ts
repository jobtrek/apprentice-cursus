import { PASSING_GRADE } from '@/data/dashboard';

export type AverageStatus = {
    label: string;
    /** Classe de texte Tailwind. */
    class: string;
    /** Couleur CSS pour les jauges SVG. */
    color: string;
};

/** Seuil à partir duquel une moyenne est considérée comme bonne. */
const GOOD_AVERAGE = 4.5;

/** Appréciation d'une moyenne sur l'échelle suisse de 1 à 6. */
export const averageStatus = (average: number): AverageStatus => {
    if (average >= GOOD_AVERAGE) {
        return {
            label: 'Bonne',
            class: 'text-success',
            color: 'var(--success)',
        };
    }

    if (average >= PASSING_GRADE) {
        return {
            label: 'Suffisante',
            class: 'text-warning',
            color: 'var(--warning)',
        };
    }

    return {
        label: 'Insuffisante',
        class: 'text-destructive',
        color: 'var(--destructive)',
    };
};

/** `02.03.2026` → `2026-03-02`, comparable comme une chaîne. */
export const sortableDate = (date: string): string =>
    date.split('.').reverse().join('-');
