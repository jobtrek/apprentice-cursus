import type { Grade } from '@/types/grade';
import type { GradeTreeNode } from '@/types/GradeTreeNode';

/** Notes d'exemple partagées par les feuilles (données factices). */
const placeholderGrades: Grade[] = [
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

/** Arbre factice — Informaticien CFC (cf. docs/project-docs/grade_tree_IT.md). */
export const mockITTree: GradeTreeNode = {
    id: 0,
    title: 'Note finale CFC',
    weight: null,
    value: 5.0,
    grades: [],
    children: [
        {
            id: 1,
            title: 'TPI',
            weight: 40,
            value: 5.0,
            grades: [],
            children: [
                {
                    id: 11,
                    title: 'Exécution et résultat du travail',
                    weight: 50,
                    value: 5.0,
                    grades: placeholderGrades,
                    children: [],
                },
                {
                    id: 12,
                    title: 'Documentation',
                    weight: 20,
                    value: 5.0,
                    grades: placeholderGrades,
                    children: [],
                },
                {
                    id: 13,
                    title: 'Présentation et entretien professionnel',
                    weight: 30,
                    value: 5.0,
                    grades: placeholderGrades,
                    children: [],
                },
            ],
        },
        {
            id: 2,
            title: 'Culture générale',
            weight: 20,
            value: 5.5,
            grades: placeholderGrades,
            children: [],
        },
        {
            id: 3,
            title: 'Compétences en informatique',
            weight: 30,
            value: 5.0,
            grades: [],
            children: [
                {
                    id: 31,
                    title: 'Modules école pro',
                    weight: 80,
                    value: 5.0,
                    grades: placeholderGrades,
                    children: [],
                },
                {
                    id: 32,
                    title: 'Modules CIE',
                    weight: 20,
                    value: 5.0,
                    grades: placeholderGrades,
                    children: [],
                },
            ],
        },
        {
            id: 4,
            title: 'Compétences de base élargies',
            weight: 10,
            value: 4.5,
            grades: placeholderGrades,
            children: [],
        },
    ],
};

/** Arbre factice — Employé de commerce CFC (cf. docs/project-docs/grade_tree_EC.md). */
export const mockECTree: GradeTreeNode = {
    id: 0,
    title: 'Note finale CFC',
    weight: null,
    value: 5.0,
    grades: [],
    children: [
        {
            id: 1,
            title: 'Travail pratique',
            weight: 30,
            value: 5.0,
            grades: [],
            children: [
                {
                    id: 11,
                    title: 'Étude de cas spécifique à la branche',
                    weight: 100,
                    value: 5.0,
                    grades: placeholderGrades,
                    children: [],
                },
            ],
        },
        {
            id: 2,
            title: 'Connaissances professionnelles et culture générale',
            weight: 30,
            value: 5.0,
            grades: [],
            children: [
                {
                    id: 21,
                    title: "Travail au sein de structures d'activité dynamiques",
                    weight: 20,
                    value: 5.0,
                    grades: placeholderGrades,
                    children: [],
                },
                {
                    id: 22,
                    title: 'Interaction dans un milieu interconnecté',
                    weight: 20,
                    value: 5.0,
                    grades: placeholderGrades,
                    children: [],
                },
                {
                    id: 23,
                    title: 'Coordination des processus de travail',
                    weight: 20,
                    value: 5.0,
                    grades: placeholderGrades,
                    children: [],
                },
                {
                    id: 24,
                    title: 'Relations clients et fournisseurs',
                    weight: 20,
                    value: 5.0,
                    grades: placeholderGrades,
                    children: [],
                },
                {
                    id: 25,
                    title: 'Technologies numériques',
                    weight: 20,
                    value: 5.0,
                    grades: placeholderGrades,
                    children: [],
                },
            ],
        },
        {
            id: 3,
            title: "Note d'expérience",
            weight: 40,
            value: 5.0,
            grades: [],
            children: [
                {
                    id: 31,
                    title: 'Formation à la pratique professionnelle',
                    weight: 25,
                    value: 5.0,
                    grades: placeholderGrades,
                    children: [],
                },
                {
                    id: 32,
                    title: 'Enseignement des connaissances professionnelles',
                    weight: 50,
                    value: 5.0,
                    grades: placeholderGrades,
                    children: [],
                },
                {
                    id: 33,
                    title: 'Cours interentreprises',
                    weight: 25,
                    value: 5.0,
                    grades: placeholderGrades,
                    children: [],
                },
            ],
        },
    ],
};
