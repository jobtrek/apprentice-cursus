export type ApprenticeshipCode = 'IT' | 'EC';

/** Matches App\Http\Resources\ApprenticeResource. */
export interface Apprentice {
    id: number;
    name: string;
    apprenticeship: ApprenticeshipCode | null;
    coach: string | null;
    isActive: boolean;
}
