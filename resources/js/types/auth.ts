export type UserRole = 'apprentice' | 'trainer' | 'coach';

export type ApprenticeshipCode = 'it' | 'ec';

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    role: UserRole;
    apprenticeship_id: number | null;
    is_active: boolean;
    is_mp: boolean | null;
    email_verified_at: string | null;
    [key: string]: unknown;
};

/** Abilities computed from the Laravel policies (HandleInertiaRequests::permissions). */
export type Permissions = {
    createGrade: boolean;
    viewPortfolio: boolean;
    createProject: boolean;
    viewApprentices: boolean;
    viewAdministration: boolean;
};

export type Auth = {
    user: User;
    apprenticeship: ApprenticeshipCode | null;
    can: Permissions;
};
