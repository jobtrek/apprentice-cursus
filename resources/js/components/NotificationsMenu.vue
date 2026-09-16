<script setup lang="ts">
import { BellIcon } from '@lucide/vue';
import { computed } from 'vue';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { getInitials } from '@/composables/useInitials';
import {
    formatNotificationDate,
    useNotifications,
} from '@/composables/useNotifications';
import { cn } from '@/lib/utils';

const { notifications, unreadCount, markAsRead, markAllAsRead } =
    useNotifications();

const triggerLabel = computed(() =>
    unreadCount.value > 0
        ? `Notifications, ${unreadCount.value} non lue${unreadCount.value > 1 ? 's' : ''}`
        : 'Notifications',
);
</script>

<template>
    <Popover>
        <PopoverTrigger
            :aria-label="triggerLabel"
            class="text-muted-foreground hover:bg-accent hover:text-foreground focus-visible:ring-ring/50 relative inline-flex size-9 items-center justify-center rounded-md transition-colors focus-visible:ring-[3px] focus-visible:outline-none"
        >
            <BellIcon class="size-5" />

            <span
                v-if="unreadCount > 0"
                class="bg-primary text-primary-foreground absolute top-1 right-1 flex size-4 items-center justify-center rounded-full text-[10px] leading-none font-medium"
            >
                {{ unreadCount }}
            </span>
        </PopoverTrigger>

        <PopoverContent
            align="end"
            :side-offset="8"
            class="w-[22rem] overflow-hidden p-0"
        >
            <div class="flex items-center justify-between border-b px-4 py-3">
                <p class="text-sm font-semibold">Notifications</p>

                <button
                    type="button"
                    class="text-muted-foreground hover:text-foreground text-xs transition-colors disabled:pointer-events-none disabled:opacity-50"
                    :disabled="unreadCount === 0"
                    @click="markAllAsRead"
                >
                    Tout marquer comme lu
                </button>
            </div>

            <p
                v-if="notifications.length === 0"
                class="text-muted-foreground px-4 py-6 text-center text-sm"
            >
                Aucune notification pour le moment.
            </p>

            <ul v-else class="max-h-96 divide-y overflow-y-auto">
                <li
                    v-for="notification in notifications"
                    :key="notification.id"
                >
                    <button
                        type="button"
                        class="hover:bg-accent/60 flex w-full items-start gap-3 px-4 py-3 text-left transition-colors"
                        :class="cn(!notification.read && 'bg-accent/40')"
                        @click="markAsRead(notification.id)"
                    >
                        <span
                            class="mt-2 size-1.5 shrink-0 rounded-full"
                            :class="
                                notification.read
                                    ? 'bg-transparent'
                                    : 'bg-primary'
                            "
                        />

                        <span
                            class="bg-muted text-muted-foreground flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-medium"
                        >
                            {{ getInitials(notification.author.name) }}
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block text-sm">
                                <span class="font-semibold">
                                    {{ notification.author.name }}
                                </span>
                                <span class="text-muted-foreground">
                                    · {{ notification.author.role }}
                                </span>
                                <span class="font-medium">
                                    {{ notification.action }}
                                </span>
                            </span>

                            <span
                                class="text-muted-foreground mt-0.5 block truncate text-sm"
                            >
                                {{ notification.target }}
                            </span>

                            <span
                                class="text-muted-foreground mt-1 block text-xs"
                            >
                                {{
                                    formatNotificationDate(
                                        notification.created_at,
                                    )
                                }}
                            </span>
                        </span>
                    </button>
                </li>
            </ul>
        </PopoverContent>
    </Popover>
</template>
