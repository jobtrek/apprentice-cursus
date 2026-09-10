<script setup>
import { Form, Link } from '@inertiajs/vue3'
import AuthenticatedSessionController from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { create as createGrade, show as showGrade } from '@/routes/grades';

</script>
<template>

        <nav
            class="flex items-center justify-between border-b px-6 py-4"
        >
            <div class="flex items-center gap-6">
                <Link :href="home()" class="font-medium">Jobtrek</Link>
                <Link
                    :href="showGrade(1)"
                    class="text-muted-foreground text-sm hover:underline"
                >
                    Détail de la note
                </Link>
                <Link
                    :href="createGrade()"
                    class="text-muted-foreground text-sm hover:underline"
                >
                    Ajouter une note
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
