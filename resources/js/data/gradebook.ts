import type { Grade, GradeMenu } from '@/types/grade';

// Carnet de notes : la structure des domaines et les notes par domaine restent
// des données de démonstration (les mêmes pour tous les apprentis). Les lignes
// des tableaux sont les notes réelles de l'apprenti·e, envoyées par le serveur.

export interface DomainGrade {
    title: string;
    weight: string;
    grade: number;
}

export const FINAL_GRADE: DomainGrade = {
    title: 'Note finale CFC',
    weight: '100%',
    grade: 5.0,
};

export const DOMAIN_GRADES: DomainGrade[] = [
    { title: 'TPI', weight: '40%', grade: 5.0 },
    { title: 'Compétences en informatique', weight: '30%', grade: 5.0 },
    { title: 'Compétences de base élargies', weight: '10%', grade: 4.5 },
    { title: 'Culture générale', weight: '30%', grade: 5.5 },
];

const columns = [
    { key: 'module', label: 'Module', class: 'w-[30%]' },
    { key: 'subject', label: 'Matière', class: 'w-[30%]' },
    { key: 'value', label: 'Note', class: 'w-[12%]' },
    { key: 'semester', label: 'Semestre', class: 'w-[12%]' },
    { key: 'date', label: 'Date' },
];

/** Arbre d'affichage du carnet : chaque table reçoit les notes de l'apprenti·e. */
export const gradeTables = (grades: Grade[]): GradeMenu[] => [
    {
        title: 'Compétences en informatique',
        columns,
        grades,
        subMenu: [
            { title: 'Modules école pro', columns, grades },
            { title: 'Modules CIE', columns, grades },
        ],
    },
    { title: 'Compétences de base élargies', columns, grades },
    { title: 'Culture générale', columns, grades },
    { title: 'TPI', columns, grades },
];
