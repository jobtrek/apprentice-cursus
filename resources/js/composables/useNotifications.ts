import { router, usePage } from '@inertiajs/vue3';
import { Temporal } from 'temporal-polyfill';
import { computed } from 'vue';
import { formatDate } from '@/lib/date';
import notificationsRoutes from '@/routes/notifications';

/**
 * `created_at` est un instant ISO 8601 avec fuseau : ramené dans le fuseau du
 * navigateur, seule façon de comparer « aujourd'hui » et « hier » du point de
 * vue de la personne qui lit.
 */
const toLocalDateTime = (date: string): Temporal.PlainDateTime =>
    Temporal.Instant.from(date)
        .toZonedDateTimeISO(Temporal.Now.timeZoneId())
        .toPlainDateTime();

const time = (date: Temporal.PlainDateTime): string =>
    date.toPlainTime().toString({ smallestUnit: 'minute' });

/**
 * Libellé relatif façon maquette : « Il y a 2 heures » le jour même,
 * « Hier à 16:40 » la veille, puis la date « 26.08.2026 ».
 */
export const formatNotificationDate = (date: string): string => {
    const created = toLocalDateTime(date);
    const now = Temporal.Now.plainDateTimeISO();

    const { minutes } = created.until(now, { largestUnit: 'minute' });

    if (minutes < 1) {
        return "À l'instant";
    }

    if (minutes < 60) {
        return `Il y a ${minutes} minute${minutes > 1 ? 's' : ''}`;
    }

    const createdDay = created.toPlainDate();
    const today = now.toPlainDate();

    if (createdDay.equals(today)) {
        const hours = Math.floor(minutes / 60);

        return `Il y a ${hours} heure${hours > 1 ? 's' : ''}`;
    }

    if (createdDay.equals(today.subtract({ days: 1 }))) {
        return `Hier à ${time(created)}`;
    }

    return formatDate(createdDay.toString());
};

/** Date complète pour le détail d'une notification : « 16.09.2026 à 08:15 ». */
export const formatNotificationFullDate = (date: string): string => {
    const created = toLocalDateTime(date);

    return `${formatDate(created.toPlainDate().toString())} à ${time(created)}`;
};

/**
 * Seules les notifications changent : on évite de recharger les props de la
 * page courante (la boîte de réception garde sa sélection et son filtre).
 */
const refreshNotifications = {
    only: ['notifications', 'unreadNotificationsCount'],
    preserveScroll: true,
    preserveState: true,
};

/**
 * Les notifications viennent de la prop Inertia partagée `notifications` ;
 * « lu » / « non lu » sont enregistrés côté serveur puis la prop est rechargée.
 */
export const useNotifications = () => {
    const page = usePage();

    const notifications = computed(() => page.props.notifications);
    const unreadCount = computed(() => page.props.unreadNotificationsCount);

    const markAsRead = (id: string): void => {
        if (
            notifications.value.find((item) => item.id === id)?.read !== false
        ) {
            return;
        }

        router.put(notificationsRoutes.read.url(id), {}, refreshNotifications);
    };

    const markAsUnread = (id: string): void => {
        router.delete(notificationsRoutes.unread.url(id), refreshNotifications);
    };

    const markAllAsRead = (): void => {
        router.put(notificationsRoutes.readAll.url(), {}, refreshNotifications);
    };

    return {
        notifications,
        unreadCount,
        markAsRead,
        markAsUnread,
        markAllAsRead,
    };
};
