/**
 * Champs alignés sur la migration `create_projects_table` de la branche
 * `database/migrations`. `screenshots` et `position` n'y existent pas encore :
 * ils sont pour l'instant portés uniquement par le front (voir README de la PR).
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
    track: string;
    promotion: string;
};
