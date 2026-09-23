/**
 * Forme partagée par `App\Http\Resources\ProjectResource`. `screenshots` est
 * toujours vide tant que le stockage des captures n'existe pas côté serveur.
 */
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
    screenshots: string[];
    skill_ids: number[];
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
