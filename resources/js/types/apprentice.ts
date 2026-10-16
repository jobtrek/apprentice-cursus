/** Forme envoyée par `ApprenticeResource` (app/Http/Resources/ApprenticeResource.php). */
export interface Apprentice {
    id: number;
    name: string;
    /** « IT », « EC », ou null sans filière. */
    track: 'IT' | 'EC' | null;
    /**
     * Année d'apprentissage (1 à 4) déduite des dates des notes, faute de date
     * de début en base. Null sans note.
     */
    year: number | null;
    isActive: boolean;
    /** Variante maturité professionnelle (MP). */
    isMp: boolean;
    /** Nom du coach, null sans coach. */
    coach: string | null;
    coachId: number | null;
    /** Formateurs du plan de l'apprenti·e (« A, B »), null sans formateur. */
    trainer: string | null;
}

/** Statistiques de notes calculées par le serveur. */
export interface ApprenticeStats {
    grades_count: number;
    /** Moyenne arrondie au dixième, null sans note. */
    average: number | null;
    /** Date ISO, null sans note. */
    last_grade_date: string | null;
}

/** Ligne de `ApprenticeList::for()` : un·e apprenti·e suivi·e et ses notes. */
export interface ApprenticeListItem extends Apprentice {
    stats: ApprenticeStats;
}

/** Apprenti·e proposé·e par « Ajouter un apprenti » : sans coach. */
export interface AssignableApprentice {
    id: number;
    name: string;
    track: string | null;
}

/** Coach proposé dans les listes de l'admin. */
export interface SupervisorOption {
    id: number;
    name: string;
}

/** Note récente d'un·e apprenti·e suivi·e (`ApprenticeList::latestGrades`). */
export interface SupervisedGrade {
    id: number;
    apprentice_id: number;
    apprentice: string;
    title: string;
    value: number;
    /** Date ISO. */
    date: string;
    comments_count: number;
}

/** Notes d'un mois (`ApprenticeList::monthlyActivity`), du plus ancien au plus récent. */
export interface MonthlyActivity {
    /** `2026-03`. */
    month: string;
    grades: number;
    /** Apprentis suivis ayant au moins une note ce mois-là. */
    apprentices: number;
}
