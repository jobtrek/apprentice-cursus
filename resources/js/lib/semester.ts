/**
 * Période de formation d'une date, d'après la règle des user stories :
 * août–décembre = semestre 1 de l'année scolaire, janvier–juillet = semestre 2.
 */
export interface TrainingPeriod {
    /** Année d'apprentissage, à partir de 1. */
    year: number;
    /** Semestre dans l'année scolaire : 1 ou 2. */
    semesterInYear: 1 | 2;
    /** Semestre dans tout le cursus, de 1 à 8. */
    semester: number;
}

/** Première année scolaire (celle qui commence en août) contenant `date`. */
const schoolYearStart = (date: Date): number =>
    date.getMonth() >= 7 ? date.getFullYear() : date.getFullYear() - 1;

/**
 * @param startYear année civile du mois d'août où l'apprentissage a commencé
 */
export const trainingPeriod = (
    startYear: number,
    date: Date = new Date(),
): TrainingPeriod => {
    const year = Math.max(1, schoolYearStart(date) - startYear + 1);
    const semesterInYear = date.getMonth() >= 7 ? 1 : 2;

    return {
        year,
        semesterInYear,
        semester: (year - 1) * 2 + semesterInYear,
    };
};
