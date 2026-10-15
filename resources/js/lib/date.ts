import { Temporal } from 'temporal-polyfill';

/**
 * Le serveur envoie les dates en ISO (`2026-03-02`) : comparables comme des
 * chaînes, et mises en forme ici seulement, pour l'affichage.
 */

const capitalize = (text: string): string =>
    text.charAt(0).toUpperCase() + text.slice(1);

/** `2026-03-02` → `02.03.2026`. */
export const formatDate = (iso: string): string =>
    Temporal.PlainDate.from(iso).toLocaleString('fr-CH', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });

/** `2025-03-01` → `Mars 2025`. */
export const formatMonthYear = (iso: string): string =>
    capitalize(
        Temporal.PlainDate.from(iso).toLocaleString('fr-CH', {
            month: 'long',
            year: 'numeric',
        }),
    );

const monthName = new Intl.DateTimeFormat('fr-CH', { month: 'long' });

/** Les douze mois pour une liste : `{ value: '1', label: 'Janvier' }`… */
export const MONTHS = Array.from({ length: 12 }, (_, index) => ({
    value: String(index + 1),
    label: capitalize(monthName.format(new Date(2000, index, 1))),
}));

const relativeFormat = new Intl.RelativeTimeFormat('fr', { numeric: 'auto' });

/** `2026-03-02` → « il y a 3 jours », « hier », « il y a 2 mois »… */
export const relativeDate = (iso: string): string => {
    const days = Temporal.PlainDate.from(iso).until(
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
