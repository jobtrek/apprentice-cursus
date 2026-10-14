<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { type Situation, situationOf, TONES } from '@/lib/apprentice';
import { apprentisdashboard } from '@/routes';
import type { ApprenticeListItem } from '@/types/apprentice';

/**
 * Où en est le groupe d'un coup d'œil : une barre empilée par situation, du
 * plus au moins urgent, avec les mêmes catégories que la liste des apprentis.
 */
const props = defineProps<{ apprentices: ApprenticeListItem[] }>();

const ORDER: Situation['key'][] = [
    'insufficient',
    'stale',
    'no-grades',
    'on-track',
];

const segments = computed(() => {
    const groups = new Map<
        Situation['key'],
        { situation: Situation; count: number }
    >();

    for (const apprentice of props.apprentices) {
        const situation = situationOf(apprentice);
        const group = groups.get(situation.key);

        groups.set(situation.key, {
            situation,
            count: (group?.count ?? 0) + 1,
        });
    }

    return ORDER.flatMap((key) => {
        const group = groups.get(key);

        return group ? [{ key, ...group }] : [];
    });
});

const total = computed(() => props.apprentices.length);

const share = (count: number): number =>
    total.value > 0 ? (count / total.value) * 100 : 0;
</script>

<template>
    <Card class="shadow-xs">
        <CardHeader>
            <CardTitle>Situation du groupe</CardTitle>
            <CardDescription>
                {{ total }} apprenti{{ total > 1 ? 's' : '' }} suivi{{
                    total > 1 ? 's' : ''
                }}
            </CardDescription>
            <CardAction>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="apprentisdashboard()">
                        Liste
                        <ChevronRightIcon aria-hidden="true" />
                    </Link>
                </Button>
            </CardAction>
        </CardHeader>
        <CardContent class="flex flex-col gap-5">
            <!-- La légende porte les chiffres : la barre est décorative. -->
            <div
                class="bg-muted flex h-3 w-full gap-0.5 overflow-hidden rounded-full"
                aria-hidden="true"
            >
                <span
                    v-for="segment in segments"
                    :key="segment.key"
                    class="h-full first:rounded-l-full last:rounded-r-full"
                    :class="TONES[segment.situation.tone].bar"
                    :style="{ width: `${share(segment.count)}%` }"
                />
            </div>

            <ul class="flex flex-col gap-3">
                <li
                    v-for="segment in segments"
                    :key="segment.key"
                    class="flex items-center gap-3 text-sm"
                >
                    <span
                        class="size-2.5 shrink-0 rounded-full"
                        :class="TONES[segment.situation.tone].dot"
                        aria-hidden="true"
                    />
                    <span class="flex-1">{{ segment.situation.label }}</span>
                    <span class="font-semibold tabular-nums">
                        {{ segment.count }}
                    </span>
                    <span
                        class="text-muted-foreground w-10 text-right text-xs tabular-nums"
                    >
                        {{ Math.round(share(segment.count)) }} %
                    </span>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
