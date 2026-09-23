<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BookOpenIcon,
    FolderKanbanIcon,
    PlusCircleIcon,
    SettingsIcon,
    UsersIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import { PageContainer, PageHeader } from '@/components/page';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { administration, apprentisdashboard } from '@/routes';
import grades from '@/routes/grades';
import portfolio from '@/routes/portfolio';

const page = usePage();

const firstName = computed(
    () => page.props.auth?.user?.name?.split(/\s+/)[0] ?? '',
);

const shortcuts = [
    {
        title: 'Carnet de notes',
        description: 'Consultez les notes et les moyennes par domaine.',
        href: grades.dashboard(),
        icon: BookOpenIcon,
    },
    {
        title: 'Ajouter une note',
        description: 'Saisissez une nouvelle note et déposez le justificatif.',
        href: grades.create(),
        icon: PlusCircleIcon,
    },
    {
        title: 'Portfolio',
        description: 'Gérez vos projets et exportez votre portfolio.',
        href: portfolio.index(),
        icon: FolderKanbanIcon,
    },
    {
        title: 'Apprentis',
        description: 'Suivez les apprentis dont vous êtes responsable.',
        href: apprentisdashboard(),
        icon: UsersIcon,
    },
    {
        title: 'Administration',
        description: 'Gérez les comptes et le référentiel des matières.',
        href: administration(),
        icon: SettingsIcon,
    },
];
</script>

<template>
    <Head title="Accueil" />

    <PageContainer size="lg">
        <PageHeader
            :title="firstName ? `Bonjour ${firstName}` : 'Accueil'"
            description="Que souhaitez-vous faire aujourd'hui ?"
        />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="shortcut in shortcuts"
                :key="shortcut.title"
                :href="shortcut.href"
                class="group focus-visible:ring-ring/50 rounded-xl focus-visible:ring-[3px] focus-visible:outline-none"
            >
                <Card
                    class="group-hover:border-primary/50 group-hover:bg-accent/40 h-full transition-colors"
                >
                    <CardHeader>
                        <div
                            class="bg-primary/10 text-primary mb-2 flex size-10 items-center justify-center rounded-lg"
                        >
                            <component
                                :is="shortcut.icon"
                                class="size-5"
                                aria-hidden="true"
                            />
                        </div>
                        <CardTitle>{{ shortcut.title }}</CardTitle>
                        <CardDescription>
                            {{ shortcut.description }}
                        </CardDescription>
                    </CardHeader>
                </Card>
            </Link>
        </div>
    </PageContainer>
</template>
