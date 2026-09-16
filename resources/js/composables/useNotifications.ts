import { computed, ref } from 'vue';
import notificationsData from '@/data/notifications.json';
import type { AppNotification } from '@/types/notification';

/**
 * État partagé des notifications. Comme pour le portfolio, les données
 * viennent d'un JSON tant que le backend ne les expose pas via Inertia :
 * les « marquer comme lu » restent en mémoire et sont perdus au rechargement
 * complet de la page.
 */
const notifications = ref<AppNotification[]>(
    structuredClone(notificationsData.notifications) as AppNotification[],
);

function isSameDay(a: Date, b: Date): boolean {
    return (
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate()
    );
}

function pad(value: number): string {
    return String(value).padStart(2, '0');
}

/**
 * Libellé relatif façon maquette : « Il y a 2 heures » le jour même,
 * « Hier à 16:40 » la veille, puis la date « 26.08.2026 ».
 */
export function formatNotificationDate(date: string): string {
    const created = new Date(date);
    const now = new Date();
    const minutes = Math.floor((now.getTime() - created.getTime()) / 60_000);

    if (minutes < 1) {
        return "À l'instant";
    }

    if (minutes < 60) {
        return `Il y a ${minutes} minute${minutes > 1 ? 's' : ''}`;
    }

    if (isSameDay(created, now)) {
        const hours = Math.floor(minutes / 60);

        return `Il y a ${hours} heure${hours > 1 ? 's' : ''}`;
    }

    const yesterday = new Date(now);
    yesterday.setDate(yesterday.getDate() - 1);

    if (isSameDay(created, yesterday)) {
        return `Hier à ${pad(created.getHours())}:${pad(created.getMinutes())}`;
    }

    return `${pad(created.getDate())}.${pad(created.getMonth() + 1)}.${created.getFullYear()}`;
}

export function useNotifications() {
    const unreadCount = computed(
        () => notifications.value.filter((item) => !item.read).length,
    );

    function markAsRead(id: number): void {
        const notification = notifications.value.find((item) => item.id === id);

        if (notification) {
            notification.read = true;
        }
    }

    function markAllAsRead(): void {
        notifications.value.forEach((item) => {
            item.read = true;
        });
    }

    return {
        notifications,
        unreadCount,
        markAsRead,
        markAllAsRead,
    };
}
