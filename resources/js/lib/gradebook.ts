import type { Grade } from '@/types/grade';

/**
 * Calcul des moyennes du carnet, comme pour le CFC : les notes sont sur les
 * feuilles de l'arbre d'évaluation, chaque nœud parent fait la moyenne pondérée
 * de ses enfants notés (poids de `evaluation_node_connections`), puis le
 * `rounding_step` du nœud est appliqué. Reprend la maquette apprentice-cursus-design.
 */

export interface TreeNode {
    id: number;
    name: string;
    /** Nœud composite (moyenne pondérée) ; une feuille porte des notes. */
    aggregated: boolean;
    rounding_step: number | null;
    period_scope: 'semester' | 'cursus';
    children: { id: number; weight: number }[];
}

/** Envoyé par `App\Support\Gradebook\GradebookTree`. */
export interface GradeTree {
    root: number;
    nodes: Record<number, TreeNode>;
}

/** Note minimale de réussite. */
export const PASS = 4;
export const GRADE_MAX = 6;
/** Semestres de la formation (grades.semester 1–8). */
export const SEMESTERS = 8;

export const fmt = (n: number): string => n.toFixed(1);

export const plural = (n: number, word: string): string =>
    `${n} ${word}${n > 1 ? 's' : ''}`;

/** `33.33` → « 33.33 », `40` → « 40 ». */
export const fmtWeight = (weight: number): string =>
    Number.isInteger(weight) ? String(weight) : weight.toFixed(2);

/** « Travail pratique individuel (TPI) » → « TPI » ; sinon le nom entier. */
export const shortName = (name: string): string =>
    /\(([^)]+)\)\s*$/.exec(name)?.[1] ?? name;

/** `d.m.Y` → valeur triable `Ymd`. */
const sortKey = (date: string): string => date.split('.').reverse().join('');

export const byDateDesc = (a: Grade, b: Grade): number =>
    sortKey(b.date).localeCompare(sortKey(a.date)) || b.id - a.id;

export type Status = 'good' | 'warn' | 'bad';

/** Validé (≥ 4.5), Suffisant (≥ 4), À risque. */
export function status(value: number): [Status, string] {
    const ratio = value / GRADE_MAX;

    if (ratio >= 0.75) {
        return ['good', 'Validé'];
    }

    if (ratio >= PASS / GRADE_MAX) {
        return ['warn', 'Suffisant'];
    }

    return ['bad', 'À risque'];
}

const roundTo = (value: number, step: number | null): number =>
    step ? Math.round(value / step) * step : value;

export function createGradebook(tree: GradeTree, allGrades: Grade[]) {
    const node = (id: number): TreeNode | undefined => tree.nodes[id];

    /** Moyenne pondérée d'un nœud sur `grades` ; null si rien n'est noté dessous. */
    function nodeValue(
        id: number,
        grades: Grade[] = allGrades,
        round = true,
    ): number | null {
        const current = node(id);

        if (!current) {
            return null;
        }

        let value: number;

        if (!current.aggregated) {
            const own = grades.filter((grade) => grade.node_id === id);

            if (own.length === 0) {
                return null;
            }

            value =
                own.reduce((sum, grade) => sum + grade.value, 0) / own.length;
        } else {
            let sum = 0;
            let weights = 0;

            for (const child of current.children) {
                const childValue = nodeValue(child.id, grades, false);

                if (childValue !== null) {
                    sum += childValue * child.weight;
                    weights += child.weight;
                }
            }

            if (weights === 0) {
                return null;
            }

            value = sum / weights;
        }

        return round ? roundTo(value, current.rounding_step) : value;
    }

    const leafCache = new Map<number, Set<number>>();

    function leavesOf(id: number): Set<number> {
        const cached = leafCache.get(id);

        if (cached) {
            return cached;
        }

        const current = node(id);
        const leaves = new Set<number>(
            !current || !current.aggregated
                ? [id]
                : current.children.flatMap((child) => [...leavesOf(child.id)]),
        );
        leafCache.set(id, leaves);

        return leaves;
    }

    const gradesUnder = (id: number, grades: Grade[] = allGrades): Grade[] => {
        const leaves = leavesOf(id);

        return grades.filter((grade) => leaves.has(grade.node_id));
    };

    const weightOf = (parent: number, child: number): number =>
        node(parent)?.children.find((entry) => entry.id === child)?.weight ?? 0;

    const inSemester = (semester: number): Grade[] =>
        allGrades.filter((grade) => grade.semester === semester);

    /** Un domaine a une moyenne par semestre s'il contient une feuille semestrielle. */
    const hasSemesterLeaves = (id: number): boolean =>
        [...leavesOf(id)].some(
            (leaf) => node(leaf)?.period_scope === 'semester',
        );

    /** Domaines = enfants de la racine (TPI, Culture générale…). */
    const domains = node(tree.root)?.children.map((child) => child.id) ?? [];

    return {
        root: tree.root,
        domains,
        name: (id: number) => node(id)?.name ?? '',
        node,
        nodeValue,
        leavesOf,
        gradesUnder,
        weightOf,
        inSemester,
        hasSemesterLeaves,
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
