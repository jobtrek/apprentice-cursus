<script setup lang="ts">
import { Form, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import AppLogo from '@/components/AppLogo.vue';
import NotificationsMenu from '@/components/NotificationsMenu.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import { administration, apprentisdashboard, home, logout } from '@/routes';
import grades from '@/routes/grades';
import portfolio from '@/routes/portfolio';

const page = usePage();

const currentUrl = computed(() => page.url);
const user = computed(() => page.props.auth?.user ?? null);

const tabs = [
    { label: 'Accueil', href: home() },
    { label: 'Carnet de notes', href: grades.dashboard() },
    { label: 'Ajouter une note', href: grades.create() },
    { label: 'Portfolio', href: portfolio.index() },
    { label: 'Apprentis', href: apprentisdashboard() },
    { label: 'Administration', href: administration() },
];

// « / » ne doit correspondre qu'à lui-même : sinon toutes les pages
// seraient considérées comme des sous-pages de l'accueil.
const isActive = (href: { url: string }) =>
    currentUrl.value === href.url ||
    (href.url !== '/' && currentUrl.value.startsWith(`${href.url}/`));
</script>

<template>
    <nav class="bg-background sticky top-0 z-40 border-b">
        <div class="flex h-14 items-center gap-2 px-4 sm:gap-6 sm:px-6">
            <Link
                :href="home()"
                class="focus-visible:ring-ring/50 shrink-0 rounded-md focus-visible:ring-[3px] focus-visible:outline-none"
            >
                <AppLogo class="h-7 w-auto" />
            </Link>

            <div
                class="flex h-full min-w-0 flex-1 items-center gap-1 overflow-x-auto"
            >
                <Link
                    v-for="tab in tabs"
                    :key="tab.href.url"
                    :href="tab.href"
                    class="relative flex h-full items-center px-3 text-sm whitespace-nowrap transition-colors"
                    :class="
                        isActive(tab.href)
                            ? 'text-foreground font-medium'
                            : `text-muted-foreground hover:text-foreground`
                    "
                >
                    {{ tab.label }}

                    <span
                        v-if="isActive(tab.href)"
                        class="bg-primary absolute inset-x-2 bottom-0 h-0.5 rounded-full"
                    />
                </Link>
            </div>

            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                <AppearanceToggle />

                <NotificationsMenu />

                <div v-if="user" class="flex items-center gap-2">
                    <span class="hidden text-sm font-medium sm:inline">
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
                        size="sm"
                        :disabled="processing"
                        data-test="logout-button"
                    >
                        Se déconnecter
                    </Button>
                </Form>
            </div>
        </div>
    </nav>
</template>
