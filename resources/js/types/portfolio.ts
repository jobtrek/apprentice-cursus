/** Forme partagée par `App\Http\Resources\ProjectResource`. */
export type PortfolioProject = {
    id: number;
    title: string;
    organization: string | null;
    description: string;
    responsibilities: string | null;
    technologies: string | null;
    repository_url: string | null;
    demo_path: string | null;
    date_start: string;
    date_end: string | null;
    screenshots: ProjectScreenshot[];
    skill_ids: number[];
};

/** `url` pointe vers une route authentifiée, pas vers un fichier public. */
export type ProjectScreenshot = {
    id: number;
    url: string;
};

export type Skill = {
    id: number;
    name: string;
};

export type PortfolioOwner = {
    name: string;
    /** Nom de la filière d'apprentissage, null si non attribuée. */
    track: string | null;
};
