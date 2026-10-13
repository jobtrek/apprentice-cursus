<script setup lang="ts">
import { computed } from 'vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { MonthlyActivity } from '@/types/apprentice';

/**
 * Notes saisies par mois sur l'année écoulée : montre si le groupe tient son
 * carnet à jour. Le survol d'une barre donne le détail du mois.
 */
const props = defineProps<{ activity: MonthlyActivity[] }>();

const shortMonth = new Intl.DateTimeFormat('fr-CH', { month: 'short' });
const longMonth = new Intl.DateTimeFormat('fr-CH', {
    month: 'long',
    year: 'numeric',
});

/** `2026-03` → date du 1er du mois, pour les libellés. */
const toDate = (month: string): Date => new Date(`${month}-01T00:00:00`);

const max = computed(() =>
    Math.max(1, ...props.activity.map(({ grades }) => grades)),
);

const total = computed(() =>
    props.activity.reduce((sum, { grades }) => sum + grades, 0),
);

const bars = computed(() =>
    props.activity.map((entry, index) => ({
        ...entry,
        label: shortMonth.format(toDate(entry.month)).replace('.', ''),
        title: `${longMonth.format(toDate(entry.month))} : ${entry.grades} note${entry.grades > 1 ? 's' : ''}, ${entry.apprentices} apprenti${entry.apprentices > 1 ? 's' : ''}`,
        height: (entry.grades / max.value) * 100,
        current: index === props.activity.length - 1,
    })),
);
</script>

<template>
    <Card class="shadow-xs">
        <CardHeader>
            <CardTitle>Activité des 12 derniers mois</CardTitle>
            <CardDescription>
                {{ total }} note{{ total > 1 ? 's' : '' }} saisie{{
                    total > 1 ? 's' : ''
                }}, par mois de test
            </CardDescription>
        </CardHeader>
        <CardContent>
            <p v-if="total === 0" class="text-muted-foreground text-sm">
                Aucune note sur les 12 derniers mois.
            </p>
            <ol
                v-else
                class="flex h-44 items-end gap-1.5 sm:gap-2"
                aria-label="Notes saisies par mois"
            >
                <li
                    v-for="bar in bars"
                    :key="bar.month"
                    class="group flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1.5"
                    :title="bar.title"
                >
                    <span
                        class="text-muted-foreground text-xs tabular-nums opacity-0 transition-opacity group-hover:opacity-100"
                        :class="{
                            'opacity-100': bar.current && bar.grades > 0,
                        }"
                        aria-hidden="true"
                    >
                        {{ bar.grades }}
                    </span>
                    <span
                        class="w-full rounded-t-md transition-colors"
                        :class="
                            bar.grades === 0
                                ? 'bg-muted h-1'
                                : bar.current
                                  ? 'bg-primary'
                                  : 'bg-primary/40 group-hover:bg-primary/70'
                        "
                        :style="
                            bar.grades > 0
                                ? { height: `${bar.height}%` }
                                : undefined
                        "
                        aria-hidden="true"
                    />
                    <span
                        class="text-muted-foreground w-full truncate text-center text-[11px] capitalize"
                        aria-hidden="true"
                    >
                        {{ bar.label }}
                    </span>
                    <span class="sr-only">{{ bar.title }}</span>
                </li>
            </ol>
        </CardContent>
    </Card>
</template>
