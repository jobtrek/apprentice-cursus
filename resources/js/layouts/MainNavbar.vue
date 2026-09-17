<script setup lang="ts">
import { Form, Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import AppearanceToggle from "@/components/AppearanceToggle.vue";
import AppLogo from "@/components/AppLogo.vue";
import NotificationsMenu from "@/components/NotificationsMenu.vue";
import { Button } from "@/components/ui/button";
import { getInitials } from "@/composables/useInitials";
import { administration, apprentisdashboard, home, logout } from "@/routes";
import grades from "@/routes/grades";
import portfolio from "@/routes/portfolio";

const page = usePage();

const currentUrl = computed(() => page.url);
const user = computed(() => page.props.auth?.user ?? null);

const tabs = [
    { label: "Accueil", href: home() },
    { label: "Ajouter une note", href: grades.create() },
    { label: "Portfolio", href: portfolio.index() },
    { label: "Voir mes apprentis", href: apprentisdashboard() },
    { label: "Administration", href: administration() },
];

const isActive = (href: { url: string }) =>
    currentUrl.value === href.url ||
    currentUrl.value.startsWith(`${href.url}/`);
</script>

<template>
    <nav class="sticky top-0 z-40 border-b bg-background">
        <div class="flex h-14 items-center gap-2 px-4 sm:gap-6 sm:px-6">
            <Link
                :href="home()"
                class="shrink-0 rounded-md focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
            >
                <AppLogo class="h-7 w-auto" />
            </Link>

            <div class="flex h-full min-w-0 flex-1 items-center gap-1">
                <Link
                    v-for="tab in tabs"
                    :key="tab.href.url"
                    :href="tab.href"
                    class="relative flex h-full items-center px-3 text-sm whitespace-nowrap transition-colors"
                    :class="
                        isActive(tab.href)
                            ? 'font-medium text-foreground'
                            : `
                              text-muted-foreground
                              hover:text-foreground
                            `
                    "
                >
                    {{ tab.label }}

                    <span
                        v-if="isActive(tab.href)"
                        class="absolute inset-x-2 bottom-0 h-0.5 rounded-full bg-primary"
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

                    <span
                        class="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium text-muted-foreground"
                        :title="user.name"
                    >
                        {{ getInitials(user.name) }}
                    </span>
                </div>

                <Form
                    v-bind="logout.form()"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        variant="ghost"
                        size="sm"
                        :disabled="processing"
                        data-test="logout-button"
                    >
                        Log out
                    </Button>
                </Form>
            </div>
        </div>
    </nav>
</template>
