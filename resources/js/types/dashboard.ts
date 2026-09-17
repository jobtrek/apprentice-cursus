/**
 * Données des tableaux de bord. Comme le reste du front, elles viennent de
 * `@/data/dashboard.json` tant que le backend ne les expose pas via Inertia.
 */
export type GradeScale = {
    min: number;
    max: number;
    /** Seuil de réussite : 4 selon l'ordonnance SEFRI. */
    passing: number;
};

export type TimelinePoint = {
    label: string;
    average: number;
};

export type ScoreItem = {
    id: string;
    name: string;
    score: number;
    /** Libellé court pour l'axe des abscisses, le nom complet va dans l'infobulle. */
    short: string;
};

/**
 * Une composante de la note finale. `weight` est la pondération en pourcent,
 * `null` pour la note globale qui n'en a pas.
 */
export type Requirement = {
    id: string;
    label: string;
    score: number;
    weight: number | null;
};

export type ApprenticeDashboard = {
    finalEstimate: number;
    requirements: Requirement[];
    timeline: TimelinePoint[];
    branches: ScoreItem[];
    portfolio: {
        projects: number;
        skillsCovered: number;
        skillsTotal: number;
    };
};

export type ApprenticeSummary = {
    id: string;
    name: string;
    year: string;
    average: number;
    /** Écart avec la période précédente. */
    delta: number;
    /** Prénom seul, pour l'axe des abscisses. */
    short: string;
};

export type CoachDashboard = {
    timeline: TimelinePoint[];
    apprentices: ApprenticeSummary[];
};

export type DashboardData = {
    scale: GradeScale;
    apprentice: ApprenticeDashboard;
    coach: CoachDashboard;
};
