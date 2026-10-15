/** Miroir de `App\Enums\UserRole`. */
export type UserRole = 'apprentice' | 'coach' | 'trainer' | 'admin';

/** Utilisateur connecté, champs partagés par `HandleInertiaRequests` (`auth.user`). */
export type User = {
    id: number;
    name: string;
    email: string;
    role: UserRole | null;
    formation_plan_id: number | null;
    /** Variante maturité professionnelle ; false hors apprenti·e. */
    is_mp: boolean;
};

/** Booléens de permission partagés par `HandleInertiaRequests` (`auth.can`). */
export type Can = {
    createGrade: boolean;
    viewOwnGrades: boolean;
    viewSupervisedGrades: boolean;
    managePortfolio: boolean;
    viewApprentices: boolean;
    /** Admin local : tableau de bord OCR. */
    viewOcrStats: boolean;
};

export type Auth = {
    user: User;
    can: Can;
};
