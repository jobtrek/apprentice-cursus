<script setup>
import { Form, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedSessionController from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import { Button } from '@/components/ui/button';
import { apprentisdashboard, home } from '@/routes';
import grades from '@/routes/grades';
import portfolio from '@/routes/portfolio';

const currentUrl = computed(() => usePage().url);

const tabs = [
    { label: 'Accueil', href: home() },
    { label: 'Ajouter une note', href: grades.create() },
    { label: 'Portfolio', href: portfolio.index() },
    { label: 'Voir mes apprentis', href: apprentisdashboard() },
];

const isActive = (href) =>
    currentUrl.value === href.url ||
    currentUrl.value.startsWith(`${href.url}/`);
</script>
<template>
    <nav class="bg-muted/30 flex flex-wrap items-center justify-between gap-2 border-b px-3">
        <div class="flex flex-wrap items-end gap-1 pt-2">
            <Link :href="home()" class="mr-2 mb-2 self-center font-medium"
                >Jobtrek</Link
            >

            <Link
                v-for="tab in tabs"
                :key="tab.href.url"
                :href="tab.href"
                class="rounded-t-lg border border-b-0 px-4 py-2 text-sm transition-colors"
                :class="
                    isActive(tab.href)
                        ? 'bg-background border-border text-foreground'
                        : 'text-muted-foreground hover:text-foreground hover:bg-background/50 border-transparent'
                "
            >
                {{ tab.label }}
            </Link>
        </div>
        <Form
            v-bind="AuthenticatedSessionController.destroy.form()"
            v-slot="{ processing }"
        >
            <Button
                type="submit"
                variant="outline"
                :disabled="processing"
                data-test="logout-button"
            >
                Log out
            </Button>
        </Form>
    </nav>
</template>
