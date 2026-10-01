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
    /** Formateur de la filière (config/apprenticeships.php), null si aucun. */
    trainer: string | null;
    /** Un coach voit tous les apprentis mais n'ouvre que les siens. */
    canView: boolean;
    /** Le coach peut se l'attribuer : apprenti·e actif·ve sans coach. */
    canAssign: boolean;
}

/** Statistiques de notes calculées par le serveur. */
export interface ApprenticeStats {
    grades_count: number;
    /** Moyenne arrondie au dixième, null sans note. */
    average: number | null;
    /** Format `d.m.Y`, null sans note. */
    last_grade_date: string | null;
}

export interface ApprenticeListItem extends Apprentice {
    /** Null pour un·e apprenti·e que l'utilisateur ne peut pas ouvrir. */
    stats: ApprenticeStats | null;
}

/** Coach proposé dans la liste de l'admin. */
export interface SupervisorOption {
    id: number;
    name: string;
}
