export type Track = 'IT' | 'EC';

export type SubjectStatus = 'Active' | 'Désactivée';

/** Matière du référentiel, telle qu'affichée dans l'administration. */
export interface Subject {
    id: number;
    name: string;
    domain: string;
    /** Libellé complet de la filière, ex. « Informatique ». */
    track: string;
    status: SubjectStatus;
    hasGrades: boolean;
}

/** Matière en cours d'édition : la filière y est sous forme abrégée. */
export type EditableSubject = Omit<Subject, 'track'> & { track: Track };

/** Données saisies dans le formulaire de création d'une matière. */
export interface NewSubjectPayload {
    name: string;
    domain: string;
    track: Track;
}

/** Données émises à l'enregistrement d'une matière existante. */
export type SubjectSavePayload = NewSubjectPayload & { id: number };
