/** Miroir de `App\Enums\UserRole`. */
export type UserRole = 'apprentice' | 'coach' | 'trainer' | 'admin';

/** Utilisateur connecté, champs partagés par `HandleInertiaRequests` (`auth.user`). */
export type User = {
    id: number;
    name: string;
    email: string;
    role: UserRole | null;
    apprenticeship_context_id: number | null;
};

/** Booléens de permission partagés par `HandleInertiaRequests` (`auth.can`). */
export type Can = {
    createGrade: boolean;
    viewOwnGrades: boolean;
    viewSupervisedGrades: boolean;
    managePortfolio: boolean;
    viewApprentices: boolean;
};

export type Auth = {
    user: User;
    can: Can;
};
