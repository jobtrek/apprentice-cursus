import { usePage } from '@inertiajs/vue3';
import type { Grade, Grading } from '@/types/grade';

/**
 * Lecture du carnet : les moyennes CFC (pondérées, arrondies au `rounding_step`
 * de chaque nœud) sont calculées par `App\Support\Gradebook\GradebookTree` ;
 * ce fichier ne fait que les lire et les mettre en forme.
 */

export interface TreeNode {
    id: number;
    name: string;
    /** Nœud composite (moyenne pondérée) ; une feuille porte des notes. */
    aggregated: boolean;
    rounding_step: number | null;
    children: { id: number; weight: number }[];
    /** Moyenne arrondie ; null si rien n'est noté dessous. */
    value: number | null;
    /** Nombre de notes sous le nœud. */
    count: number;
    /** Toutes les évaluations comptées sous le nœud ont une note. */
    complete: boolean;
    /** Feuilles sous le nœud (lui-même pour une feuille). */
    leaves: number[];
}

/** Feuille de l'arbre : la seule sorte de nœud qui porte des notes. */
export type Branch = { id: number; name: string };

export type BranchGroup = { name: string; branches: Branch[] };

/** Envoyé par `App\Support\Gradebook\GradebookTree`. */
export interface GradeTree {
    root: number;
    nodes: Record<number, TreeNode>;
    /** Branches du formulaire de note, groupées par parent direct. */
    groups: BranchGroup[];
    /** Moyenne de la racine pour chaque semestre (1–8) ; null sans note. */
    semesters: Record<number, number | null>;
    /** 8 semestres en IT, 6 en EC. */
    semester_count: number;
    /** Dernier semestre noté (1 sans note), faute de date de début en base. */
    current_semester: number;
    /** Semestre noté avant le courant ; null sans. */
    previous_semester: number | null;
    /** Domaines sans note : la note finale est provisoire sans eux. */
    missing_domains: number[];
    /** Notes sous le seuil de réussite. */
    failed_count: number;
}

/** Seuils de `App\Support\Gradebook\Grading`, partagés par Inertia (`grading`). */
export const grading = (): Grading => usePage().props.grading;

export const fmt = (n: number): string => n.toFixed(1);

export const plural = (n: number, word: string): string =>
    `${n} ${word}${n > 1 ? 's' : ''}`;

/** Poids fractionnaire → pourcentage : `0.4` → « 40 », `0.3333` → « 33.33 ». */
export const fmtWeight = (weight: number): string => {
    const percent = Math.round(weight * 10000) / 100;

    return Number.isInteger(percent) ? String(percent) : percent.toFixed(2);
};

/** « Travail pratique individuel (TPI) » → « TPI » ; sinon le nom entier. */
export const shortName = (name: string): string =>
    /\(([^)]+)\)\s*$/.exec(name)?.[1] ?? name;

/** `d.m.Y` → valeur triable `Ymd`. */
const sortKey = (date: string): string => date.split('.').reverse().join('');

export const byDateDesc = (a: Grade, b: Grade): number =>
    sortKey(b.date).localeCompare(sortKey(a.date)) || b.id - a.id;

export type Status = 'good' | 'warn' | 'bad';

/** Validé (≥ `good`), Suffisant (≥ `pass`), À risque. */
export function status(value: number): [Status, string] {
    const { pass, good } = grading();

    if (value >= good) {
        return ['good', 'Validé'];
    }

    if (value >= pass) {
        return ['warn', 'Suffisant'];
    }

    return ['bad', 'À risque'];
}

/**
 * Classe de couleur d'une note (`is-good`, `is-warn`, `is-bad`) : mêmes seuils
 * que `status()` et que les teintes du profil, pour qu'une note ait la même
 * couleur sur toutes les pages.
 */
export const gradeTone = (value: number): string => `is-${status(value)[0]}`;

export function createGradebook(tree: GradeTree, allGrades: Grade[]) {
    const node = (id: number): TreeNode | undefined => tree.nodes[id];

    /** Notes à afficher sous ce nœud (listes du carnet). */
    const gradesUnder = (id: number): Grade[] => {
        const leaves = new Set(node(id)?.leaves ?? [id]);

        return allGrades.filter((grade) => leaves.has(grade.node_id));
    };

    const weightOf = (parent: number, child: number): number =>
        node(parent)?.children.find((entry) => entry.id === child)?.weight ?? 0;

    const inSemester = (semester: number): Grade[] =>
        allGrades.filter((grade) => grade.semester === semester);

    /** Domaines = enfants de la racine (TPI, Culture générale…). */
    const domains = node(tree.root)?.children.map((child) => child.id) ?? [];

    return {
        root: tree.root,
        domains,
        semesterCount: tree.semester_count,
        currentSemester: tree.current_semester,
        previousSemester: tree.previous_semester,
        missingDomains: tree.missing_domains,
        failedCount: tree.failed_count,
        name: (id: number) => node(id)?.name ?? '',
        node,
        nodeValue: (id: number) => node(id)?.value ?? null,
        count: (id: number) => node(id)?.count ?? 0,
        isComplete: (id: number = tree.root) => node(id)?.complete ?? false,
        semesterValue: (semester: number) => tree.semesters[semester] ?? null,
        gradesUnder,
        weightOf,
        inSemester,
    };
}

export type Gradebook = ReturnType<typeof createGradebook>;

/** Barre aux coins arrondis côté valeur (4 px) et base carrée. */
export function barPath(
    x0: number,
    x1: number,
    top: number,
    base: number,
): string {
    const r = Math.min(4, base - top, (x1 - x0) / 2);

    return `M${x0} ${base} V${top + r} Q${x0} ${top} ${x0 + r} ${top} H${x1 - r} Q${x1} ${top} ${x1} ${top + r} V${base} Z`;
}

/** Libellé court d'un domaine : « Compétences en informatique » → « Informatique ». */
export function domainLabel(name: string): string {
    const short = shortName(name).replace(/^Compétences (en|de) /, '');

    return short.charAt(0).toUpperCase() + short.slice(1);
}
