import type { Grade } from './grade';
export interface GradeTreeNode {
    id: number;
    title: string;
    weight: number | null;
    value: number | null;
    grades: Grade[];
    children: GradeTreeNode[];
}
