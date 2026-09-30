<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChevronRightIcon,
    GraduationCapIcon,
    MonitorIcon,
    UserRoundXIcon,
    UsersIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import StatTile from '@/components/dashboard/StatTile.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { apprentisdashboard } from '@/routes';
import apprenticesRoutes from '@/routes/apprentices';
import type { Apprentice } from '@/types/apprentice';

const props = defineProps<{
    apprentices: Apprentice[];
}>();

/** Les apprentis désactivés ne sont pas « suivis » : ils restent visibles dans la liste complète. */
const active = computed(() => props.apprentices.filter((a) => a.isActive));

const countIn = (code: 'IT' | 'EC') =>
    active.value.filter((a) => a.apprenticeship === code).length;

const withoutCoach = computed(() => active.value.filter((a) => !a.coach));
</script>

<template>
    <section
        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        aria-label="Indicateurs"
    >
        <StatTile
            label="Apprentis suivis"
            :value="String(active.length)"
            hint="Actifs, IT et EC confondus"
            :icon="UsersIcon"
        />
        <StatTile
            label="IT"
            :value="String(countIn('IT'))"
            hint="Apprentis actifs en IT"
            :icon="MonitorIcon"
        />
        <StatTile
            label="EC"
            :value="String(countIn('EC'))"
            hint="Apprentis actifs en EC"
            :icon="GraduationCapIcon"
        />
        <StatTile
            label="Sans coach"
            :value="String(withoutCoach.length)"
            hint="Aucun coach assigné"
            :icon="UserRoundXIcon"
        />
    </section>

    <section>
        <Card>
            <CardHeader>
                <CardTitle>Sans coach</CardTitle>
                <CardDescription>
                    Apprentis actifs sans coach assigné
                </CardDescription>
                <CardAction>
                    <Button as-child variant="ghost" size="sm">
                        <Link :href="apprentisdashboard()">
                            Tous
                            <ChevronRightIcon aria-hidden="true" />
                        </Link>
                    </Button>
                </CardAction>
            </CardHeader>
            <CardContent>
                <ul v-if="withoutCoach.length" class="divide-y">
                    <li v-for="apprentice in withoutCoach" :key="apprentice.id">
                        <Link
                            :href="apprenticesRoutes.show(apprentice.id)"
                            class="hover:bg-muted/50 focus-visible:ring-ring/50 -mx-2 flex items-center gap-3 rounded-md px-2 py-3 transition-colors focus-visible:ring-[3px] focus-visible:outline-none"
                        >
                            <p
                                class="min-w-0 flex-1 truncate text-sm font-medium"
                            >
                                {{ apprentice.name }}
                            </p>
                            <span class="text-muted-foreground text-xs">
                                {{ apprentice.apprenticeship ?? '—' }}
                            </span>
                        </Link>
                    </li>
                </ul>
                <p v-else class="text-muted-foreground text-sm">
                    Tous les apprentis actifs ont un coach.
                </p>
            </CardContent>
        </Card>
    </section>
</template>
