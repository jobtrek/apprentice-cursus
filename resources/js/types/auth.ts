/** Miroir de `App\Enums\UserRole`. */
export type UserRole =
    | 'apprentice'
    | 'coach'
    | 'trainer'
    | 'admin'
    | 'super_admin';

/** Utilisateur connecté, tel que sérialisé par `App\Models\User`. */
export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    role: UserRole;
    is_active: boolean;
    /** Maturité professionnelle. `null` hors apprentis. */
    is_mp: boolean | null;
    apprenticeship_id: number | null;
    coach_id: number | null;
    trainer_id: number | null;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
