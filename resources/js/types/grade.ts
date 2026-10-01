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

/** Nœud de l'arbre d'affichage du carnet de notes (domaine ou sous-domaine). */
export interface GradeMenu {
    title: string;
    columns: { key: string; label: string; class?: string }[];
    grades: Grade[];
    subMenu?: GradeMenu[];
}
