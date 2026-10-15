import { Temporal } from 'temporal-polyfill';
import { type Status, status } from '@/lib/gradebook';
import type { ApprenticeListItem, SituationKey } from '@/types/apprentice';

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

const AVERAGE_STATUS: Record<Status, AverageStatus> = {
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

/** Appréciation d'une moyenne : mêmes seuils que `status()`, libellés de moyenne. */
export const averageStatus = (average: number): AverageStatus =>
    AVERAGE_STATUS[status(average)[0]];

/** `02.03.2026` → `2026-03-02`, comparable comme une chaîne. */
export const sortableDate = (date: string): string =>
    date.split('.').reverse().join('-');

/** `1` → `1re année`, `3` → `3e année`. */
export const yearLabel = (year: number): string =>
    `${year === 1 ? '1re' : `${year}e`} année`;

/** État de suivi d'un·e apprenti·e, du plus au moins urgent. */
export type Situation = {
    key: SituationKey;
    label: string;
    tone: Tone;
};

const SITUATIONS: Record<SituationKey, Omit<Situation, 'key'>> = {
    insufficient: { label: 'Moyenne insuffisante', tone: 'destructive' },
    'no-grades': { label: 'Aucune note', tone: 'muted' },
    stale: { label: 'Sans note récente', tone: 'warning' },
    'on-track': { label: 'À jour', tone: 'success' },
};

/** Libellé et couleur de la situation décidée par le serveur (`ApprenticeList`). */
export const situationOf = ({
    stats,
}: Pick<ApprenticeListItem, 'stats'>): Situation => ({
    key: stats.situation,
    ...SITUATIONS[stats.situation],
});

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
