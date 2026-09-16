<script setup lang="ts">
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
import { BellIcon } from '@lucide/vue';
import { computed } from 'vue';

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
            class="
              relative inline-flex size-9 items-center justify-center rounded-md
              text-muted-foreground transition-colors
              hover:bg-accent hover:text-foreground
              focus-visible:ring-[3px] focus-visible:ring-ring/50
              focus-visible:outline-none
            "
        >
            <BellIcon class="size-5" />

            <span
                v-if="unreadCount > 0"
                class="
                  absolute top-1 right-1 flex size-4 items-center justify-center
                  rounded-full bg-primary text-[10px] leading-none font-medium
                  text-primary-foreground
                "
            >
                {{ unreadCount }}
            </span>
        </PopoverTrigger>

        <PopoverContent
            align="end"
            :side-offset="8"
            class="w-88 overflow-hidden p-0"
        >
            <div class="flex items-center justify-between border-b px-4 py-3">
                <p class="text-sm font-semibold">Notifications</p>

                <button
                    type="button"
                    class="
                      text-xs text-muted-foreground transition-colors
                      hover:text-foreground
                      disabled:pointer-events-none disabled:opacity-50
                    "
                    :disabled="unreadCount === 0"
                    @click="markAllAsRead"
                >
                    Tout marquer comme lu
                </button>
            </div>

            <p
                v-if="notifications.length === 0"
                class="px-4 py-6 text-center text-sm text-muted-foreground"
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
                        class="
                          flex w-full items-start gap-3 px-4 py-3 text-left
                          transition-colors
                          hover:bg-accent/60
                        "
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
                            class="
                              flex size-8 shrink-0 items-center justify-center
                              rounded-full bg-muted text-xs font-medium
                              text-muted-foreground
                            "
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
                                class="
                                  mt-0.5 block truncate text-sm
                                  text-muted-foreground
                                "
                            >
                                {{ notification.target }}
                            </span>

                            <span
                                class="mt-1 block text-xs text-muted-foreground"
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
