import { Temporal } from 'temporal-polyfill';
import { PASSING_GRADE } from '@/data/dashboard';
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

/** Seuil à partir duquel une moyenne est considérée comme bonne. */
const GOOD_AVERAGE = 4.5;

/** Appréciation d'une moyenne sur l'échelle suisse de 1 à 6. */
export const averageStatus = (average: number): AverageStatus => {
    if (average >= GOOD_AVERAGE) {
        return {
            label: 'Bonne',
            tone: 'success',
            class: 'text-success',
            color: 'var(--success)',
        };
    }

    if (average >= PASSING_GRADE) {
        return {
            label: 'Suffisante',
            tone: 'warning',
            class: 'text-warning',
            color: 'var(--warning)',
        };
    }

    return {
        label: 'Insuffisante',
        tone: 'destructive',
        class: 'text-destructive',
        color: 'var(--destructive)',
    };
};

/** `02.03.2026` → `2026-03-02`, comparable comme une chaîne. */
export const sortableDate = (date: string): string =>
    date.split('.').reverse().join('-');

/** Au-delà, un·e apprenti·e sans nouvelle note est signalé·e. */
export const STALE_AFTER_DAYS = 90;

/** Aucune note, ou la dernière date de plus de STALE_AFTER_DAYS jours. */
export const hasNoRecentGrade = (lastGradeDate: string | null): boolean =>
    lastGradeDate === null ||
    sortableDate(lastGradeDate) <
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

const relativeFormat = new Intl.RelativeTimeFormat('fr', { numeric: 'auto' });

/** `02.03.2026` → « il y a 3 jours », « hier », « il y a 2 mois »… */
export const relativeDate = (date: string): string => {
    const days = Temporal.PlainDate.from(sortableDate(date)).until(
        Temporal.Now.plainDateISO(),
    ).days;

    if (days < 30) {
        return relativeFormat.format(-days, 'day');
    }

    if (days < 365) {
        return relativeFormat.format(-Math.round(days / 30), 'month');
    }

    return relativeFormat.format(-Math.round(days / 365), 'year');
};
