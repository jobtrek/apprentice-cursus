/**
 * Notifications affichées dans la navbar. Aucune table `notifications`
 * n'existe encore côté backend : les données viennent pour l'instant de
 * `@/data/notifications.json` (voir `useNotifications`).
 */
export type NotificationAuthor = {
    name: string;
    /** Rôle affiché après le nom : « Coach », « Formateur »… */
    role: string;
};

export type AppNotification = {
    id: number;
    author: NotificationAuthor;
    /** Ce que l'auteur a fait : « a commenté votre note ». */
    action: string;
    /** Sur quoi porte l'action : module, épreuve ou projet. */
    target: string;
    /** Date ISO 8601. */
    created_at: string;
    read: boolean;
};
