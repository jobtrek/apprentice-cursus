<script setup lang="ts">
import { Form, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedSessionController from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import { Button } from '@/components/ui/button';
import portfolio from '@/routes/portfolio';
import { home } from '@/routes';

const page = usePage();
</script>

<template>
    <div class="bg-background min-h-svh">
        <header class="flex items-center justify-between border-b px-6 py-4">
            <div class="flex items-center gap-6">
                <Link :href="home()" class="font-medium">{{
                    page.props.name
                }}</Link>

                <nav class="flex items-center gap-4 text-sm">
                    <Link
                        :href="portfolio.index()"
                        class="text-muted-foreground hover:text-foreground"
                    >
                        Portfolio
                    </Link>
                </nav>
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
        </header>

        <main class="mx-auto max-w-5xl px-6 py-10">
            <slot />
        </main>
    </div>
</template>
