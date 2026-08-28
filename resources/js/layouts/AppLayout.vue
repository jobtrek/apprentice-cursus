<script setup lang="ts">
import { Form, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedSessionController from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';

const page = usePage();
</script>

<template>
    <div class="bg-background min-h-svh">
        <header
            class="flex items-center justify-between border-b px-6 py-4"
        >
            <Link :href="home()" class="font-medium">{{
                page.props.name
            }}</Link>

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

        <main class="mx-auto max-w-sm px-6 py-10">
            <slot />
        </main>
    </div>
</template>
