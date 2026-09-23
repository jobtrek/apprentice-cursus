<script setup lang="ts">
import { Form, Link, usePage } from '@inertiajs/vue3';
import { LogOutIcon, MenuIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import AppLogo from '@/components/AppLogo.vue';
import NotificationsMenu from '@/components/NotificationsMenu.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { getInitials } from '@/composables/useInitials';
import { findActiveNavItem, NAV_ITEMS } from '@/constants/navigation';
import { home, logout } from '@/routes';

const page = usePage();

const user = computed(() => page.props.auth?.user ?? null);

const activeItem = computed(() => findActiveNavItem(NAV_ITEMS, page.url));

const mobileMenuOpen = ref(false);
</script>

<template>
    <nav class="bg-background sticky top-0 z-40 border-b px-4 sm:px-6">
        <div class="mx-auto flex h-14 max-w-7xl items-center gap-2 sm:gap-6">
            <Sheet v-model:open="mobileMenuOpen">
                <SheetTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="text-muted-foreground -ml-2 md:hidden"
                        aria-label="Ouvrirc le menu"
                    >
                        <MenuIcon class="size-5" aria-hidden="true" />
                    </Button>
                </SheetTrigger>

                <SheetContent side="left" class="w-72">
                    <SheetHeader>
                        <SheetTitle>
                            <AppLogo class="h-7 w-auto" />
                        </SheetTitle>
                        <SheetDescription class="sr-only">
                            Navigation principale
                        </SheetDescription>
                    </SheetHeader>

                    <div class="flex flex-col gap-1 px-4">
                        <Link
                            v-for="item in NAV_ITEMS"
                            :key="item.href.url"
                            :href="item.href"
                            class="flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors"
                            :class="
                                item === activeItem
                                    ? 'bg-accent text-accent-foreground font-medium'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                            "
                            :aria-current="
                                item === activeItem ? 'page' : undefined
                            "
                            @click="mobileMenuOpen = false"
                        >
                            <component
                                :is="item.icon"
                                class="size-4"
                                aria-hidden="true"
                            />
                            {{ item.label }}
                        </Link>
                    </div>
                </SheetContent>
            </Sheet>

            <Link
                :href="home()"
                class="focus-visible:ring-ring/50 shrink-0 rounded-md focus-visible:ring-[3px] focus-visible:outline-none"
            >
                <AppLogo class="h-7 w-auto" />
            </Link>

            <div
                class="hidden h-full min-w-0 flex-1 items-center gap-1 overflow-x-auto md:flex"
            >
                <Link
                    v-for="item in NAV_ITEMS"
                    :key="item.href.url"
                    :href="item.href"
                    class="relative flex h-full items-center px-3 text-sm whitespace-nowrap transition-colors"
                    :class="
                        item === activeItem
                            ? 'text-foreground font-medium'
                            : `text-muted-foreground hover:text-foreground`
                    "
                    :aria-current="item === activeItem ? 'page' : undefined"
                >
                    {{ item.label }}

                    <span
                        v-if="item === activeItem"
                        class="bg-primary absolute inset-x-2 bottom-0 h-0.5 rounded-full"
                    />
                </Link>
            </div>

            <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">
                <AppearanceToggle />

                <NotificationsMenu />

                <div v-if="user" class="flex items-center gap-2">
                    <span class="hidden text-sm font-medium lg:inline">
                        {{ user.name }}
                    </span>

                    <Avatar :title="user.name">
                        <AvatarFallback class="text-xs">
                            {{ getInitials(user.name) }}
                        </AvatarFallback>
                    </Avatar>
                </div>

                <Form v-bind="logout.form()" v-slot="{ processing }">
                    <Button
                        type="submit"
                        variant="ghost"
                        size="icon"
                        class="text-muted-foreground"
                        aria-label="Se déconnecter"
                        title="Se déconnecter"
                        :disabled="processing"
                        data-test="logout-button"
                    >
                        <LogOutIcon class="size-5" aria-hidden="true" />
                    </Button>
                </Form>
            </div>
        </div>
    </nav>
</template>
