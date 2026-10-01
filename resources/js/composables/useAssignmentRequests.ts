import { ref } from 'vue';
import { useNotifications } from '@/composables/useNotifications';
import type { SupervisorOption } from '@/types/apprentice';

/** Demande d'un coach à devenir le coach d'un·e apprenti·e, en attente du validateur. */
export type AssignmentRequest = {
    apprenticeId: number;
    validator: SupervisorOption;
};

/**
 * Demandes d'attribution en attente. Comme les notifications, rien n'est
 * encore enregistré côté serveur : l'état vit en mémoire et se perd au
 * rechargement complet de la page.
 */
const requests = ref<AssignmentRequest[]>([]);

export const useAssignmentRequests = () => {
    const { notify } = useNotifications();

    const pendingFor = (apprenticeId: number): AssignmentRequest | undefined =>
        requests.value.find((request) => request.apprenticeId === apprenticeId);

    /**
     * Le JSON de démo n'a pas de destinataire : la notification du validateur
     * s'ajoute à la boîte de réception partagée.
     */
    const request = (
        apprentice: { id: number; name: string },
        validator: SupervisorOption,
        coachName: string,
    ): void => {
        requests.value.push({ apprenticeId: apprentice.id, validator });

        notify({
            author: { name: coachName, role: 'Coach' },
            action: 'demande à devenir coach de',
            target: apprentice.name,
        });
    };

    return { pendingFor, request };
};
