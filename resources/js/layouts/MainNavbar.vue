<script setup>
import { Form, Link } from '@inertiajs/vue3'
import AuthenticatedSessionController from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import { Button } from '@/components/ui/button';
import { home, dashboard } from '@/routes';

</script>
<template>

        <nav
            class="flex items-center justify-between border-b px-6 py-4"
        >
            <Link :href="home()" class="font-medium">Jobtrek</Link>
            <Link :href="dashboard()">Dashboard</Link>

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
