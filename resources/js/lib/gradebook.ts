import { type GradeLevel, gradeLevel } from '@/lib/grade-scale';
import type { Grade } from '@/types/grade';

export interface TreeNode {
    id: number;
    name: string;
    /** Nœud composite (moyenne pondérée) ; une feuille porte des notes. */
    aggregated: boolean;
    rounding_step: number | null;
    children: { id: number; weight: number }[];
}

/** Envoyé par `App\Support\Gradebook\GradebookTree` (formulaire de note). */
export interface GradeTree {
    root: number;
    nodes: Record<number, TreeNode>;
}

/** Domaine ou sous-groupe du carnet, avec sa moyenne pondérée CFC. */
export interface AverageGroup {
    id: number;
    name: string;
    weight: number;
    rounding_step: number | null;
    /** Null si rien n'est noté dessous. */
    average: number | null;
    /** Notes posées sur les feuilles sous ce nœud. */
    grade_ids: number[];
}

/**
 * Moyennes du carnet, calculées comme pour le CFC par
 * `App\Support\Gradebook\Averages::of()`.
 */
export interface Averages {
    /** Note finale (racine) ; null sans note. */
    final: number | null;
    /** Toutes les évaluations comptées ont une note ; sinon la note finale est provisoire. */
    complete: boolean;
    /** Dernier semestre noté (1 sans note), faute de date de début en base. */
    current_semester: number;
    /** 8 semestres en IT, 6 en EC. */
    semester_count: number;
    /** Semestres 1–8 : moyenne et nombre de notes. */
    semesters: { semester: number; average: number | null; count: number }[];
    /** Enfants de la racine (TPI, Culture générale…) et leurs sous-groupes composites. */
    domains: (AverageGroup & { subs: AverageGroup[] })[];
}

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

export const byDateDesc = (a: Grade, b: Grade): number =>
    b.date.localeCompare(a.date) || b.id - a.id;

/** Libellé de `StatusChip` : Validé (≥ 4.5), Suffisant (≥ 4), À risque. */
export const STATUS_LABELS: Record<GradeLevel, string> = {
    good: 'Validé',
    warn: 'Suffisant',
    bad: 'À risque',
};

/** Classe de couleur d'une note (`is-good`, `is-warn`, `is-bad`). */
export const gradeTone = (value: number): string => `is-${gradeLevel(value)}`;

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
