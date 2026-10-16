import { Temporal } from 'temporal-polyfill';
import { type GradeLevel, gradeLevel, PASSING_GRADE } from '@/lib/grade-scale';
import type { ApprenticeListItem } from '@/types/apprentice';

/** Couleur sémantique partagée par les badges, jauges et tuiles. */
export type Tone = 'primary' | 'success' | 'warning' | 'destructive' | 'muted';

/**
 * Classes Tailwind de chaque ton, écrites en entier pour que Tailwind les
 * détecte : `soft` pour un fond teinté, `dot` et `bar` pour les indicateurs.
 */
export const TONES: Record<
    Tone,
    { text: string; soft: string; dot: string; bar: string }
> = {
    primary: {
        text: 'text-primary',
        soft: 'bg-primary/10 text-primary',
        dot: 'bg-primary',
        bar: 'bg-primary',
    },
    success: {
        text: 'text-success',
        soft: 'bg-success/15 text-success',
        dot: 'bg-success',
        bar: 'bg-success',
    },
    warning: {
        text: 'text-warning',
        soft: 'bg-warning/15 text-warning',
        dot: 'bg-warning',
        bar: 'bg-warning',
    },
    destructive: {
        text: 'text-destructive',
        soft: 'bg-destructive/10 text-destructive',
        dot: 'bg-destructive',
        bar: 'bg-destructive',
    },
    muted: {
        text: 'text-muted-foreground',
        soft: 'bg-muted text-muted-foreground',
        dot: 'bg-muted-foreground/50',
        bar: 'bg-muted-foreground/40',
    },
};

export type AverageStatus = {
    label: string;
    tone: Tone;
    /** Classe de texte Tailwind. */
    class: string;
    /** Couleur CSS pour les jauges SVG. */
    color: string;
};

const AVERAGE_STATUS: Record<GradeLevel, AverageStatus> = {
    good: {
        label: 'Bonne',
        tone: 'success',
        class: 'text-success',
        color: 'var(--success)',
    },
    warn: {
        label: 'Suffisante',
        tone: 'warning',
        class: 'text-warning',
        color: 'var(--warning)',
    },
    bad: {
        label: 'Insuffisante',
        tone: 'destructive',
        class: 'text-destructive',
        color: 'var(--destructive)',
    },
};

/** Appréciation d'une moyenne sur l'échelle suisse de 1 à 6. */
export const averageStatus = (average: number): AverageStatus =>
    AVERAGE_STATUS[gradeLevel(average)];

/** Au-delà, un·e apprenti·e sans nouvelle note est signalé·e. */
export const STALE_AFTER_DAYS = 90;

/** Aucune note, ou la dernière date de plus de STALE_AFTER_DAYS jours. */
export const hasNoRecentGrade = (lastGradeDate: string | null): boolean =>
    lastGradeDate === null ||
    lastGradeDate <
        Temporal.Now.plainDateISO()
            .subtract({ days: STALE_AFTER_DAYS })
            .toString();

/** `1` → `1re année`, `3` → `3e année`. */
export const yearLabel = (year: number): string =>
    `${year === 1 ? '1re' : `${year}e`} année`;

/** État de suivi d'un·e apprenti·e, du plus au moins urgent. */
export type Situation = {
    key: 'insufficient' | 'no-grades' | 'stale' | 'on-track';
    label: string;
    tone: Tone;
};

export const situationOf = ({
    stats,
}: Pick<ApprenticeListItem, 'stats'>): Situation => {
    if (stats.average !== null && stats.average < PASSING_GRADE) {
        return {
            key: 'insufficient',
            label: 'Moyenne insuffisante',
            tone: 'destructive',
        };
    }

    if (stats.grades_count === 0) {
        return { key: 'no-grades', label: 'Aucune note', tone: 'muted' };
    }

    if (hasNoRecentGrade(stats.last_grade_date)) {
        return { key: 'stale', label: 'Sans note récente', tone: 'warning' };
    }

    return { key: 'on-track', label: 'À jour', tone: 'success' };
};
