/**
 * Échelle suisse des notes, la même que `Grade::VALUE_RULES` côté serveur :
 * de 1 à 6 par demi-points, réussite dès 4.
 */
export const GRADE_MIN = 1;
export const GRADE_MAX = 6;
export const GRADE_STEP = 0.5;

/** Note minimale de réussite (CFC). */
export const PASSING_GRADE = 4;

/** À partir de là, une note ou une moyenne est bonne. */
export const GOOD_GRADE = 4.5;

export type GradeLevel = 'good' | 'warn' | 'bad';

/** Seul endroit où les seuils sont appliqués : bonne, suffisante, insuffisante. */
export const gradeLevel = (value: number): GradeLevel =>
    value >= GOOD_GRADE ? 'good' : value >= PASSING_GRADE ? 'warn' : 'bad';
