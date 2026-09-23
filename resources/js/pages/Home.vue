<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { PageContainer, PageHeader } from '@/components/page';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useNavigation } from '@/composables/useNavigation';
import { home } from '@/routes';

const page = usePage();

const firstName = computed(
    () => page.props.auth?.user?.name?.split(/\s+/)[0] ?? '',
);

const { items } = useNavigation();

// Les raccourcis reprennent la navigation du rôle, sans l'accueil lui-même.
const shortcuts = computed(() =>
    items.value.filter((item) => item.href.url !== home().url),
);
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
                :key="shortcut.href.url"
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
                        <CardTitle>{{ shortcut.label }}</CardTitle>
                        <CardDescription>
                            {{ shortcut.description }}
                        </CardDescription>
                    </CardHeader>
                </Card>
            </Link>
        </div>
    </PageContainer>
</template>
