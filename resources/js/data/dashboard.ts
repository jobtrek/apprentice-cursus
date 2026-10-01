// Données de démonstration du tableau de bord, en attendant le calcul des
// moyennes côté serveur (evaluation_results).

/** Année (août) où commence l'apprentissage de l'apprenti·e de démo. */
export const DEMO_APPRENTICESHIP_START_YEAR = 2025;

export interface SemesterAverage {
    /** Semestre du cursus, de 1 à 8. */
    semester: number;
    average: number;
}

/** Moyenne générale de l'apprenti·e de démo, semestre par semestre. */
export const SEMESTER_AVERAGES: SemesterAverage[] = [
    { semester: 1, average: 4.6 },
    { semester: 2, average: 4.9 },
    { semester: 3, average: 5.1 },
];

/** Une période affichée sur l'axe du graphique « Progression ». */
export interface ProgressPeriod {
    /** Position sur l'axe, à partir de 1. */
    position: number;
    /** Libellé court sous l'axe, ex. « S3 » ou « 2e ». */
    tick: string;
    /** Libellé complet de l'infobulle, ex. « Semestre 3 ». */
    title: string;
}

/** Une moyenne placée sur le graphique « Progression ». */
export interface ProgressPoint {
    position: number;
    average: number;
    /** Repris de la période : distingue un point « semestre » d'un point « année ». */
    title: string;
}

/** Nombre de notes saisies pendant le semestre en cours. */
export const GRADES_THIS_SEMESTER = 6;

/** Note minimale de réussite (CFC). */
export const PASSING_GRADE = 4;
