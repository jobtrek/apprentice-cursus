import type { Grade, GradeMenu } from '@/types/grade';

// Données de démonstration du carnet de notes, en attendant les notes réelles
// côté serveur. Les mêmes valeurs servent pour tous les apprentis.

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

const gradeRows: Grade[] = [
    {
        id: 1,
        title: 'Test 1',
        subject: 'Mathématique',
        value: 6,
        semester: 1,
        date: '12.03.2026',
    },
    {
        id: 2,
        title: 'Test 2',
        subject: 'Mathématique',
        value: 5.5,
        semester: 1,
        date: '02.04.2026',
    },
    {
        id: 3,
        title: 'Test 3',
        subject: 'Mathématique',
        value: 4.5,
        semester: 2,
        date: '14.05.2026',
    },
];

export const GRADE_TABLES: GradeMenu[] = [
    {
        title: 'Compétences en informatique',
        columns,
        grades: gradeRows,
        subMenu: [
            { title: 'Modules école pro', columns, grades: gradeRows },
            { title: 'Modules CIE', columns, grades: gradeRows },
        ],
    },
    { title: 'Compétences de base élargies', columns, grades: gradeRows },
    { title: 'Culture générale', columns, grades: gradeRows },
    { title: 'TPI', columns, grades: gradeRows },
];

const flattenGrades = (menus: GradeMenu[]): Grade[] =>
    menus.flatMap((menu) => [
        ...menu.grades,
        ...flattenGrades(menu.subMenu ?? []),
    ]);

/** Note de démonstration correspondant à l'identifiant d'URL, si elle existe. */
export const findGrade = (id: number): Grade | undefined =>
    flattenGrades(GRADE_TABLES).find((grade) => grade.id === id);
