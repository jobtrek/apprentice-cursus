import {
    BookOpenIcon,
    FolderKanbanIcon,
    HomeIcon,
    UsersIcon,
} from '@lucide/vue';
import type { Component } from 'vue';
import { apprentisdashboard, home } from '@/routes';
import grades from '@/routes/grades';
import portfolio from '@/routes/portfolio';
import type { UserRole } from '@/types';
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
     * `/apprentices/3` active « Apprentis », `/grades/5` « Carnet de notes ».
     */
    matches?: string[];
    /**
     * Rôles qui voient l'élément. Absent : visible par tous. Ce filtre ne fait
     * que masquer l'interface, l'accès est contrôlé côté serveur.
     */
    roles?: UserRole[];
};

/** Libellés affichés dans l'interface pour chaque rôle. */
export const ROLE_LABELS: Record<UserRole, string> = {
    apprentice: 'Apprenti·e',
    coach: 'Coach',
    trainer: 'Formateur·rice',
    admin: 'Administrateur·rice',
    super_admin: 'Super-administrateur·rice',
};

/** Rôles qui suivent des apprentis (voir role_permissions.md). */
const SUPERVISORS: UserRole[] = ['coach', 'trainer', 'admin', 'super_admin'];

/**
 * Source unique de la navigation principale : la barre de navigation et les
 * raccourcis de l'accueil en dérivent, filtrés par `navItemsForRole`.
 * L'ordre va du commun (Accueil) au plus spécifique.
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
        roles: ['apprentice'],
    },
    {
        label: 'Portfolio',
        description: 'Gérez vos projets et exportez votre portfolio.',
        href: portfolio.index(),
        icon: FolderKanbanIcon,
        roles: ['apprentice'],
    },
    {
        label: 'Apprentis',
        description: 'Suivez les notes et le portfolio de vos apprentis.',
        href: apprentisdashboard(),
        icon: UsersIcon,
        matches: ['/apprentices'],
        roles: SUPERVISORS,
    },
];

/** Éléments visibles pour `role`. Sans rôle connu, seuls les communs restent. */
export const navItemsForRole = (
    items: NavItem[],
    role: UserRole | null | undefined,
): NavItem[] =>
    items.filter(
        (item) => !item.roles || (role != null && item.roles.includes(role)),
    );

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
