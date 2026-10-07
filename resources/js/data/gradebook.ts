/** Moyenne d'un domaine et son poids dans le CFC, prête à afficher. */
export interface DomainGrade {
    title: string;
    /** Ex. « 20 % ». */
    weight: string;
    grade: number;
}
