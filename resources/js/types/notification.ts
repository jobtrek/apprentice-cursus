/**
 * Notifications affichées dans la navbar et la boîte de réception. Elles
 * viennent de la prop Inertia partagée `notifications` (voir
 * `HandleInertiaRequests` et `NotificationResource`).
 */
export type NotificationAuthor = {
    name: string;
    /** Rôle affiché après le nom : « Coach », « Formateur »… */
    role: string;
};

export type AppNotification = {
    id: string;
    author: NotificationAuthor;
    /** Ce que l'auteur a fait : « a commenté votre note ». */
    action: string;
    /** Sur quoi porte l'action : module ou épreuve. */
    target: string;
    /** Où ouvrir la note, `null` quand elle n'existe plus (note supprimée). */
    url: string | null;
    /** Date ISO 8601. */
    created_at: string;
    read: boolean;
};
