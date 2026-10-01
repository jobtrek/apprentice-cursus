/** Forme envoyée par `ApprenticeResource` (app/Http/Resources/ApprenticeResource.php). */
export interface Apprentice {
    id: number;
    name: string;
    /** « IT », « EC », ou null sans filière. */
    track: 'IT' | 'EC' | null;
    /** Déduite des notes une fois le calcul en place ; null en attendant. */
    year: number | null;
    isActive: boolean;
    /** Nom du coach, null sans coach. */
    coach: string | null;
    coachId: number | null;
    /** Nom du formateur, null sans formateur. */
    trainer: string | null;
    trainerId: number | null;
    canView: boolean;
}

/** Statistiques de notes calculées par le serveur. */
export interface ApprenticeStats {
    grades_count: number;
    /** Moyenne arrondie au dixième, null sans note. */
    average: number | null;
    /** Format `d.m.Y`, null sans note. */
    last_grade_date: string | null;
}

/** Ligne de `ApprenticeList::for()` : un·e apprenti·e suivi·e et ses notes. */
export interface ApprenticeListItem extends Apprentice {
    stats: ApprenticeStats;
}

/** Rôle sous lequel l'utilisateur s'ajoute un·e apprenti·e. */
export type AssignSelfAs = 'coach' | 'trainer';

/**
 * Apprenti·e proposé·e par « Ajouter un apprenti » : sans coach (coach), ou
 * sans formateur et de la filière du formateur (formateur).
 */
export interface AssignableApprentice {
    id: number;
    name: string;
    track: string | null;
}

/** Coach ou formateur proposé dans les listes de l'admin. */
export interface SupervisorOption {
    id: number;
    name: string;
}

/** Un formateur n'est proposé qu'aux apprentis de sa filière. */
export interface TrainerOption extends SupervisorOption {
    track: string | null;
}
