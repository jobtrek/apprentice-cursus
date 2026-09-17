export interface BranchScore {
    id: string;
    name: string;
    lastEvalLabel: string;
    score: number;
    max: number;
}

export const MAX_SCORE = 6;

export const BRANCH_SCORES: BranchScore[] = [
    { id: "programmation", name: "Programmation", lastEvalLabel: "3 sept.", score: 4.8, max: MAX_SCORE },
    { id: "reseaux-systemes", name: "Réseaux & systèmes", lastEvalLabel: "28 août", score: 3.1, max: MAX_SCORE },
    { id: "base-de-donnees", name: "Base de données", lastEvalLabel: "20 août", score: 5.6, max: MAX_SCORE },
    { id: "anglais-technique", name: "Anglais technique", lastEvalLabel: "12 août", score: 2.3, max: MAX_SCORE },
    { id: "gestion-de-projet", name: "Gestion de projet", lastEvalLabel: "5 août", score: 4.2, max: MAX_SCORE },
    { id: "culture-generale", name: "Culture générale", lastEvalLabel: "30 juil.", score: 1.4, max: MAX_SCORE },
];

export const AVERAGE_SCORE =
    BRANCH_SCORES.reduce((sum, branch) => sum + branch.score, 0) / BRANCH_SCORES.length;

export const getBranchStatus = (score: number, max: number) => {
    const ratio = max > 0 ? score / max : 0;
    if (ratio >= 0.75) {
        return { label: "Validé", class: "text-emerald-400", color: "#34d399" } as const;
    }
    if (ratio >= 4 / 6) {
        return { label: "Suffisant", class: "text-amber-400", color: "#fbbf24" } as const;
    }
    return { label: "À risque", class: "text-red-400", color: "#f87171" } as const;
};
