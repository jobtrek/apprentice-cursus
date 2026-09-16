import { Temporal } from 'temporal-polyfill';
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

const pad = (value: number): string => String(value).padStart(2, '0');

const HAS_TIME_ZONE = /(?:[Zz]|[+-]\d{2}:?\d{2})$/;

/**
 * `created_at` arrive sans fuseau dans le JSON de démo, mais Eloquent
 * sérialise en UTC (`2026-09-16T08:15:00.000000Z`). `PlainDateTime.from`
 * refuse le `Z` : on passe alors par un `Instant` qu'on ramène dans le fuseau
 * du navigateur, seule façon de comparer « aujourd'hui » et « hier » du point
 * de vue de la personne qui lit.
 */
const toLocalDateTime = (date: string): Temporal.PlainDateTime =>
    HAS_TIME_ZONE.test(date)
        ? Temporal.Instant.from(date)
              .toZonedDateTimeISO(Temporal.Now.timeZoneId())
              .toPlainDateTime()
        : Temporal.PlainDateTime.from(date);

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
        return `Hier à ${created.toPlainTime().toString({ smallestUnit: 'minute' })}`;
    }

    return `${pad(createdDay.day)}.${pad(createdDay.month)}.${createdDay.year}`;
};

export const useNotifications = () => {
    const unreadCount = computed(
        () => notifications.value.filter((item) => !item.read).length,
    );

    const markAsRead = (id: number): void => {
        const notification = notifications.value.find((item) => item.id === id);

        if (notification) {
            notification.read = true;
        }
    };

    const markAllAsRead = (): void => {
        notifications.value.forEach((item) => {
            item.read = true;
        });
    };

    return {
        notifications,
        unreadCount,
        markAsRead,
        markAllAsRead,
    };
};
