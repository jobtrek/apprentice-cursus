export interface Grade {
    id: number;
    title: string;
    subject: string;
    value: number;
    semester: number;
    date: string;
}

/** Nœud de l'arbre d'affichage du carnet de notes (domaine ou sous-domaine). */
export interface GradeMenu {
    title: string;
    columns: { key: string; label: string; class?: string }[];
    grades: Grade[];
    subMenu?: GradeMenu[];
}
