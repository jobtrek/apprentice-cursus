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

export interface ApprenticeAverage {
    /** Identifiant dans `apprentices.json`. */
    apprenticeId: string;
    average: number;
    /** Moyenne du semestre précédent, pour la tendance. */
    previousAverage: number;
}

/** Moyenne générale de chaque apprenti·e suivi·e (coach / formateur). */
export const APPRENTICE_AVERAGES: ApprenticeAverage[] = [
    { apprenticeId: '1', average: 4.8, previousAverage: 4.5 },
    { apprenticeId: '2', average: 3.7, previousAverage: 4.1 },
    { apprenticeId: '3', average: 5.2, previousAverage: 5.0 },
    { apprenticeId: '4', average: 4.4, previousAverage: 4.6 },
    { apprenticeId: '5', average: 3.9, previousAverage: 3.6 },
    { apprenticeId: '6', average: 5.5, previousAverage: 5.3 },
    { apprenticeId: '7', average: 4.1, previousAverage: 4.3 },
    { apprenticeId: '8', average: 4.9, previousAverage: 4.7 },
];

export interface YearAverage {
    /** Année d'apprentissage, de 1 à 4. */
    year: number;
    label: string;
    average: number;
    /** Nombre d'apprentis dans cette année. */
    count: number;
}

/** Note minimale de réussite (CFC). */
export const PASSING_GRADE = 4;
