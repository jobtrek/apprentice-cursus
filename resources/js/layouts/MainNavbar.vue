<script setup>
import { Form, Link } from '@inertiajs/vue3'
import AuthenticatedSessionController from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { create as createGrade, show as showGrade } from '@/routes/grades';
import { House, FileText, FilePlus, LogOut } from '@lucide/vue';

</script>
<template>

        <nav
            class="flex h-full w-56 shrink-0 flex-col justify-between border-r px-3 py-4"
        >
            <div class="flex flex-col gap-1">
                <Link
                    :href="home()"
                    class="flex items-center gap-3 rounded-md px-3 py-2 font-medium hover:bg-accent"
                >
                    <House class="size-4" />
                    Jobtrek
                </Link>
                <Link
                    :href="showGrade(1)"
                    class="text-muted-foreground flex items-center gap-3 rounded-md px-3 py-2 text-sm hover:bg-accent hover:text-foreground"
                >
                    <FileText class="size-4" />
                    Détail de la note
                </Link>
                <Link
                    :href="createGrade()"
                    class="text-muted-foreground flex items-center gap-3 rounded-md px-3 py-2 text-sm hover:bg-accent hover:text-foreground"
                >
                    <FilePlus class="size-4" />
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
                    class="w-full"
                    :disabled="processing"
                    data-test="logout-button"
                >
                    <LogOut class="size-4" />
                    Log out
                </Button>
                </Form>
            </nav>
        </template>
