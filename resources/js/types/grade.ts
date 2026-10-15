export interface Grade {
    id: number;
    /** Nœud (feuille) de l'arbre d'évaluation sur lequel porte la note. */
    node_id: number;
    title: string;
    subject: string;
    /** Ancêtres du nœud noté, du domaine jusqu'au parent direct (racine exclue). */
    path: string[];
    value: number;
    semester: number;
    date: string;
    /** Présent quand le serveur a compté les commentaires. */
    comments_count?: number;
}

/** Seuils de notation partagés par `HandleInertiaRequests` (`App\Support\Gradebook\Grading`). */
export interface Grading {
    pass: number;
    good: number;
    stale_after_days: number;
}
