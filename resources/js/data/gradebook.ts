import type { Grade, GradeMenu } from '@/types/grade';

// Carnet de notes : la structure des domaines et les notes par domaine restent
// des données de démonstration (les mêmes pour tous les apprentis). Les lignes
// des tableaux sont les notes réelles de l'apprenti·e, envoyées par le serveur,
// réparties selon le domaine de l'arbre d'évaluation auquel elles appartiennent.

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

// Domain names are supplied by the domain tree configuration.
const NODES = {
    informatique: 'Compétences en informatique',
    modulesEcole: 'Modules école professionnelle',
    modulesCie: 'Modules cours interentreprises',
    baseElargies: 'Compétences de base élargies',
    cultureGenerale: 'Culture générale',
    tpi: 'Travail pratique individuel (TPI)',
};

/** Notes dont le chemin d'ancêtres commence par `path`. */
const under = (grades: Grade[], path: string[]): Grade[] =>
    grades.filter((grade) => path.every((name, i) => grade.path[i] === name));

/**
 * Arbre d'affichage du carnet (démo) : la structure suit l'arbre IT, mais chaque
 * table ne reçoit que les notes dont le chemin d'ancêtres correspond à son nœud.
 * Les notes d'un autre arbre (ex. EC) n'apparaissent dans aucune de ces tables.
 */
export const gradeTables = (grades: Grade[]): GradeMenu[] => [
    {
        title: 'Compétences en informatique',
        columns,
        grades: under(grades, [NODES.informatique]),
        subMenu: [
            {
                title: 'Modules école pro',
                columns,
                grades: under(grades, [NODES.informatique, NODES.modulesEcole]),
            },
            {
                title: 'Modules CIE',
                columns,
                grades: under(grades, [NODES.informatique, NODES.modulesCie]),
            },
        ],
    },
    {
        title: 'Compétences de base élargies',
        columns,
        grades: under(grades, [NODES.baseElargies]),
    },
    {
        title: 'Culture générale',
        columns,
        grades: under(grades, [NODES.cultureGenerale]),
    },
    { title: 'TPI', columns, grades: under(grades, [NODES.tpi]) },
];
