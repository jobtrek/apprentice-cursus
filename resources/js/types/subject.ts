export interface Subject {
    id: number;
    name: string;
    parent_id: number;
    /** Nœud parent (domaine ou module) dont la matière fait partie de la moyenne. */
    parent: string;
    weight: number;
    tracks: string[];
    grades_count: number;
    can_delete: boolean;
}

export interface SubjectParent {
    value: string;
    label: string;
}

export interface SubjectFilters {
    search: string;
    track: 'All' | 'IT' | 'EC';
    domain: string;
}
