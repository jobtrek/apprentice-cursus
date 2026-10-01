<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    BellIcon,
    CheckCheckIcon,
    MailIcon,
    MailOpenIcon,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import TabFilter from '@/components/TabFilter.vue';
import { PageContainer, PageHeader } from '@/components/page';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { getInitials } from '@/composables/useInitials';
import {
    formatNotificationDate,
    formatNotificationFullDate,
    useNotifications,
} from '@/composables/useNotifications';
import { cn } from '@/lib/utils';

const props = defineProps<{
    /** Notification à ouvrir à l'arrivée, depuis le menu de la navbar. */
    selected: number | null;
}>();

const { notifications, unreadCount, markAsRead, markAsUnread, markAllAsRead } =
    useNotifications();

type Filter = 'all' | 'unread';

const filter = ref<Filter>('all');

const filterOptions = computed(() => [
    { label: 'Toutes', value: 'all' as const },
    {
        label:
            unreadCount.value > 0
                ? `Non lues (${unreadCount.value})`
                : 'Non lues',
        value: 'unread' as const,
    },
]);

const visibleNotifications = computed(() =>
    filter.value === 'unread'
        ? notifications.value.filter((item) => !item.read)
        : notifications.value,
);

const selectedId = ref<number | null>(null);

const selectedNotification = computed(
    () =>
        notifications.value.find((item) => item.id === selectedId.value) ??
        null,
);

function open(id: number): void {
    selectedId.value = id;
    markAsRead(id);
}

/**
 * La navbar persiste entre les visites : un clic dans son menu alors qu'on est
 * déjà sur cette page ne remonte pas le composant, seule la prop change.
 */
watch(
    () => props.selected,
    (id) => {
        if (id !== null && notifications.value.some((item) => item.id === id)) {
            open(id);
        }
    },
    { immediate: true },
);

function toggleRead(): void {
    const notification = selectedNotification.value;

    if (!notification) {
        return;
    }

    if (notification.read) {
        markAsUnread(notification.id);
    } else {
        markAsRead(notification.id);
    }
}
</script>

<template>
    <Head title="Notifications" />

    <PageContainer size="lg">
        <PageHeader
            title="Notifications"
            description="Les commentaires de vos coachs et formateurs sur vos notes et projets."
        >
            <template #actions>
                <Button
                    variant="outline"
                    :disabled="unreadCount === 0"
                    @click="markAllAsRead"
                >
                    <CheckCheckIcon aria-hidden="true" />
                    Tout marquer comme lu
                </Button>
            </template>
        </PageHeader>

        <Card
            class="grid min-h-[28rem] gap-0 overflow-hidden py-0 lg:grid-cols-[24rem_1fr]"
        >
            <!-- Liste : masquée sur mobile dès qu'une notification est ouverte. -->
            <section
                aria-label="Liste des notifications"
                :class="
                    cn(
                        'flex min-w-0 flex-col lg:border-r',
                        selectedNotification && 'hidden lg:flex',
                    )
                "
            >
                <div class="border-b p-3">
                    <TabFilter
                        v-model="filter"
                        :options="filterOptions"
                        label="Filtrer les notifications"
                    />
                </div>

                <p
                    v-if="visibleNotifications.length === 0"
                    class="text-muted-foreground px-4 py-10 text-center text-sm"
                >
                    {{
                        filter === 'unread'
                            ? 'Aucune notification non lue.'
                            : 'Aucune notification pour le moment.'
                    }}
                </p>

                <ul v-else class="divide-y overflow-y-auto lg:max-h-[36rem]">
                    <li
                        v-for="notification in visibleNotifications"
                        :key="notification.id"
                    >
                        <button
                            type="button"
                            class="hover:bg-accent/60 focus-visible:ring-ring/50 flex w-full items-start gap-3 px-4 py-3 text-left transition-colors focus-visible:ring-[3px] focus-visible:outline-none focus-visible:ring-inset"
                            :class="
                                cn(
                                    !notification.read && 'bg-accent/40',
                                    notification.id === selectedId &&
                                        'bg-accent',
                                )
                            "
                            :aria-current="
                                notification.id === selectedId
                                    ? 'true'
                                    : undefined
                            "
                            @click="open(notification.id)"
                        >
                            <span
                                class="mt-2 size-1.5 shrink-0 rounded-full"
                                :class="
                                    notification.read
                                        ? 'bg-transparent'
                                        : 'bg-primary'
                                "
                            />

                            <Avatar class="size-8">
                                <AvatarFallback class="text-xs">
                                    {{ getInitials(notification.author.name) }}
                                </AvatarFallback>
                            </Avatar>

                            <span class="min-w-0 flex-1">
                                <span class="flex items-baseline gap-2">
                                    <span
                                        class="truncate text-sm"
                                        :class="
                                            notification.read
                                                ? 'font-medium'
                                                : 'font-semibold'
                                        "
                                    >
                                        {{ notification.author.name }}
                                    </span>
                                    <span
                                        class="text-muted-foreground ml-auto shrink-0 text-xs"
                                    >
                                        {{
                                            formatNotificationDate(
                                                notification.created_at,
                                            )
                                        }}
                                    </span>
                                </span>

                                <span
                                    class="text-muted-foreground block truncate text-sm"
                                >
                                    {{ notification.action }} ·
                                    {{ notification.target }}
                                </span>
                            </span>
                        </button>
                    </li>
                </ul>
            </section>

            <!-- Détail -->
            <section
                aria-label="Détail de la notification"
                :class="
                    cn(
                        'min-w-0 flex-col',
                        selectedNotification ? 'flex' : 'hidden lg:flex',
                    )
                "
            >
                <template v-if="selectedNotification">
                    <div
                        class="flex items-center justify-between gap-2 border-b p-3"
                    >
                        <Button
                            variant="ghost"
                            size="sm"
                            class="lg:invisible"
                            @click="selectedId = null"
                        >
                            <ArrowLeftIcon aria-hidden="true" />
                            Retour
                        </Button>

                        <Button variant="ghost" size="sm" @click="toggleRead">
                            <component
                                :is="
                                    selectedNotification.read
                                        ? MailIcon
                                        : MailOpenIcon
                                "
                                aria-hidden="true"
                            />
                            {{
                                selectedNotification.read
                                    ? 'Marquer comme non lu'
                                    : 'Marquer comme lu'
                            }}
                        </Button>
                    </div>

                    <article class="flex flex-col gap-6 p-6">
                        <header class="flex items-start gap-4">
                            <Avatar class="size-11">
                                <AvatarFallback>
                                    {{
                                        getInitials(
                                            selectedNotification.author.name,
                                        )
                                    }}
                                </AvatarFallback>
                            </Avatar>

                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold">
                                        {{ selectedNotification.author.name }}
                                    </p>
                                    <Badge variant="secondary">
                                        {{ selectedNotification.author.role }}
                                    </Badge>
                                </div>
                                <p class="text-muted-foreground text-sm">
                                    <time
                                        :datetime="
                                            selectedNotification.created_at
                                        "
                                    >
                                        {{
                                            formatNotificationFullDate(
                                                selectedNotification.created_at,
                                            )
                                        }}
                                    </time>
                                </p>
                            </div>
                        </header>

                        <p class="text-lg">
                            <span class="font-semibold">
                                {{ selectedNotification.author.name }}
                            </span>
                            {{ selectedNotification.action }}
                        </p>

                        <div class="bg-muted/50 rounded-lg border p-4">
                            <p
                                class="text-muted-foreground text-xs font-medium tracking-wide uppercase"
                            >
                                Concerne
                            </p>
                            <p class="mt-1 font-medium">
                                {{ selectedNotification.target }}
                            </p>
                        </div>
                    </article>
                </template>

                <Empty v-else class="flex-1">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <BellIcon />
                        </EmptyMedia>
                        <EmptyTitle
                            >Aucune notification sélectionnée</EmptyTitle
                        >
                        <EmptyDescription>
                            Choisissez une notification dans la liste pour
                            l'afficher ici.
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>
            </section>
        </Card>
    </PageContainer>
</template>
