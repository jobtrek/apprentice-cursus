/** Forme envoyée par `ApprenticeController::summary()`. */
export interface ApprenticeSummary {
    id: number;
    name: string;
    /** « IT », « EC », ou null sans filière. */
    track: string | null;
    is_active: boolean;
    coach: { id: number; name: string } | null;
    trainer: { id: number; name: string } | null;
}

/** Statistiques de notes calculées par le serveur. */
export interface ApprenticeStats {
    grades_count: number;
    /** Moyenne arrondie au dixième, null sans note. */
    average: number | null;
    /** Format `d.m.Y`, null sans note. */
    last_grade_date: string | null;
}

export interface ApprenticeListItem extends ApprenticeSummary {
    stats: ApprenticeStats;
}

/** Rôle sous lequel l'utilisateur s'ajoute un·e apprenti·e (bouton « Ajouter »). */
export type AssignSelfAs = 'coach' | 'trainer';

/**
 * Apprenti·e proposé·e par le bouton « Ajouter » : sans coach (coach), ou sans
 * formateur et de la filière du formateur (formateur).
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

/** Les formateurs ne sont proposés qu'aux apprentis de leur filière. */
export interface TrainerOption extends SupervisorOption {
    track: string | null;
}
