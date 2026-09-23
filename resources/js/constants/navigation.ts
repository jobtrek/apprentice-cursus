import {
    BookOpenIcon,
    FolderKanbanIcon,
    HomeIcon,
    PlusCircleIcon,
    SettingsIcon,
    UsersIcon,
} from '@lucide/vue';
import type { Component } from 'vue';
import { administration, apprentisdashboard, home } from '@/routes';
import grades from '@/routes/grades';
import portfolio from '@/routes/portfolio';
import type { RouteDefinition } from '@/wayfinder';

export type NavItem = {
    label: string;
    /** Phrase affichée sur les raccourcis de la page d'accueil. */
    description?: string;
    href: RouteDefinition<'get'>;
    icon: Component;
    /**
     * Préfixes d'URL qui rendent l'élément actif, en plus de `href`. Quand
     * plusieurs éléments correspondent, le préfixe le plus long l'emporte :
     * `/grades/create` active « Ajouter une note » et non « Carnet de notes ».
     */
    matches?: string[];
};

/**
 * Source unique de la navigation principale : la barre de navigation et les
 * raccourcis de l'accueil en dérivent. C'est ici qu'il faudra filtrer par rôle.
 */
export const NAV_ITEMS: NavItem[] = [
    {
        label: 'Accueil',
        href: home(),
        icon: HomeIcon,
    },
    {
        label: 'Carnet de notes',
        description: 'Consultez les notes et les moyennes par domaine.',
        href: grades.dashboard(),
        icon: BookOpenIcon,
        matches: ['/grades'],
    },
    {
        label: 'Ajouter une note',
        description: 'Saisissez une nouvelle note et déposez le justificatif.',
        href: grades.create(),
        icon: PlusCircleIcon,
    },
    {
        label: 'Portfolio',
        description: 'Gérez vos projets et exportez votre portfolio.',
        href: portfolio.index(),
        icon: FolderKanbanIcon,
    },
    {
        label: 'Apprentis',
        description: 'Suivez les apprentis dont vous êtes responsable.',
        href: apprentisdashboard(),
        icon: UsersIcon,
    },
    {
        label: 'Administration',
        description: 'Gérez les comptes et le référentiel des matières.',
        href: administration(),
        icon: SettingsIcon,
    },
];

const stripQuery = (url: string): string => url.split(/[?#]/)[0];

/** Longueur du préfixe le plus long de `item` qui correspond à `url`, ou -1. */
const matchLength = (item: NavItem, url: string): number => {
    const path = stripQuery(url);
    const prefixes = [item.href.url, ...(item.matches ?? [])];

    return Math.max(
        -1,
        ...prefixes.map((prefix) => {
            // « / » ne correspond qu'à lui-même, sinon tout serait sous l'accueil.
            if (prefix === '/') {
                return path === '/' ? 1 : -1;
            }

            return path === prefix || path.startsWith(`${prefix}/`)
                ? prefix.length
                : -1;
        }),
    );
};

/** Élément de navigation correspondant à l'URL courante, s'il y en a un. */
export const findActiveNavItem = (
    items: NavItem[],
    url: string,
): NavItem | undefined => {
    let best: NavItem | undefined;
    let bestLength = -1;

    for (const item of items) {
        const length = matchLength(item, url);

        if (length > bestLength) {
            best = item;
            bestLength = length;
        }
    }

    return best;
};
