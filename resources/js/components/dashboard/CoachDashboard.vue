<script setup lang="ts">
import { computed } from 'vue';
import ChartCard from '@/components/dashboard/ChartCard.vue';
import ScoreBarChart from '@/components/dashboard/ScoreBarChart.vue';
import StatTile from '@/components/dashboard/StatTile.vue';
import TrendLineChart from '@/components/dashboard/TrendLineChart.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDelta, useDashboard } from '@/composables/useDashboard';

const props = defineProps<{
    /** « coach » ou « formateur », pour adapter les libellés. */
    roleLabel: string;
}>();

const { scale, coach, groupAverage, apprenticesAtRisk, apprenticesDropping } =
    useDashboard();

const barItems = computed(() =>
    [...coach.value.apprentices]
        .sort((a, b) => b.average - a.average)
        .map((item) => ({
            id: item.id,
            name: `${item.name} · ${item.year}`,
            short: item.short,
            score: item.average,
        })),
);

const subtitle = computed(
    () => `Les apprenti·e·s dont vous êtes ${props.roleLabel}.`,
);
</script>

<template>
    <div class="w-full max-w-5xl space-y-6">
        <header>
            <h1 class="text-2xl font-semibold">Suivi de mes apprenti·e·s</h1>
            <p class="text-muted-foreground mt-1 text-sm">{{ subtitle }}</p>
        </header>

        <div class="grid gap-4 sm:grid-cols-3">
            <StatTile
                label="Apprenti·e·s suivi·e·s"
                :value="String(coach.apprentices.length)"
            />
            <StatTile
                label="Sous le seuil"
                :value="String(apprenticesAtRisk.length)"
                :hint="`Moyenne inférieure à ${scale.passing.toFixed(1)}`"
            />
            <StatTile
                label="Moyenne du groupe"
                :value="groupAverage.toFixed(1)"
            />
        </div>

        <!--
            Trié par baisse la plus forte : c'est la liste qu'on ouvre en
            premier, avant même de regarder les moyennes.
        -->
        <section class="bg-card rounded-lg border p-4">
            <h2 class="text-sm font-medium">
                En baisse depuis la dernière période
            </h2>

            <Table class="mt-3">
                <TableHeader>
                    <TableRow>
                        <TableHead>Apprenti·e</TableHead>
                        <TableHead>Année</TableHead>
                        <TableHead class="text-right">Moyenne</TableHead>
                        <TableHead class="text-right">Écart</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableEmpty
                        v-if="apprenticesDropping.length === 0"
                        :colspan="4"
                    >
                        Personne en baisse sur cette période.
                    </TableEmpty>

                    <TableRow
                        v-for="item in apprenticesDropping"
                        :key="item.id"
                    >
                        <TableCell class="font-medium">
                            {{ item.name }}
                        </TableCell>
                        <TableCell>{{ item.year }}</TableCell>
                        <TableCell
                            class="text-right tabular-nums"
                            :class="
                                item.average < scale.passing
                                    ? 'text-destructive'
                                    : ''
                            "
                        >
                            {{ item.average.toFixed(1) }}
                        </TableCell>
                        <TableCell class="text-right tabular-nums">
                            {{ formatDelta(item.delta) }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            <ChartCard
                title="Moyenne par apprenti·e"
                :caption="`Classé de la plus haute à la plus basse. Seuil de réussite à ${scale.passing.toFixed(1)}.`"
            >
                <ScoreBarChart :items="barItems" :scale="scale" />

                <template #table>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Apprenti·e</TableHead>
                                <TableHead class="text-right">
                                    Moyenne
                                </TableHead>
                                <TableHead class="text-right">Écart</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="item in coach.apprentices"
                                :key="item.id"
                            >
                                <TableCell>{{ item.name }}</TableCell>
                                <TableCell class="text-right tabular-nums">
                                    {{ item.average.toFixed(1) }}
                                </TableCell>
                                <TableCell class="text-right tabular-nums">
                                    {{ formatDelta(item.delta) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </template>
            </ChartCard>

            <ChartCard
                title="Moyenne du groupe dans le temps"
                :caption="`Le filet horizontal marque le seuil de ${scale.passing.toFixed(1)}.`"
            >
                <TrendLineChart :points="coach.timeline" :scale="scale" />

                <template #table>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Période</TableHead>
                                <TableHead class="text-right">
                                    Moyenne
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="point in coach.timeline"
                                :key="point.label"
                            >
                                <TableCell>{{ point.label }}</TableCell>
                                <TableCell class="text-right tabular-nums">
                                    {{ point.average.toFixed(1) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </template>
            </ChartCard>
        </div>
    </div>
</template>
