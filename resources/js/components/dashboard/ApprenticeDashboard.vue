<script setup lang="ts">
import { computed } from 'vue';
import ChartCard from '@/components/dashboard/ChartCard.vue';
import ScoreBarChart from '@/components/dashboard/ScoreBarChart.vue';
import StatTile from '@/components/dashboard/StatTile.vue';
import TrendLineChart from '@/components/dashboard/TrendLineChart.vue';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useDashboard } from '@/composables/useDashboard';

const { scale, apprentice, blockingRequirements, branchesAtRisk } =
    useDashboard();

const barItems = computed(() =>
    apprentice.value.branches.map((branch) => ({
        id: branch.id,
        name: branch.name,
        short: branch.short,
        score: branch.score,
    })),
);

const isOnTrack = computed(() => blockingRequirements.value.length === 0);
</script>

<template>
    <div class="w-full max-w-5xl space-y-6">
        <header>
            <h1 class="text-2xl font-semibold">Mon tableau de bord</h1>
            <p class="text-muted-foreground mt-1 text-sm">
                Estimation à ce jour, sur la base des notes déjà saisies.
            </p>
        </header>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatTile
                label="Note finale CFC estimée"
                :value="apprentice.finalEstimate.toFixed(1)"
                :hint="`Seuil de réussite ${scale.passing.toFixed(1)}`"
            />
            <StatTile
                label="Branches sous le seuil"
                :value="String(branchesAtRisk.length)"
                :hint="
                    branchesAtRisk.map((branch) => branch.name).join(', ') ||
                    'Aucune'
                "
            />
            <StatTile
                label="Projets au portfolio"
                :value="String(apprentice.portfolio.projects)"
            />
            <StatTile
                label="Compétences couvertes"
                :value="`${apprentice.portfolio.skillsCovered}/${apprentice.portfolio.skillsTotal}`"
                hint="Catalogue des compétences IT"
            />
        </div>

        <!--
            Les trois conditions >= 4 de l'ordonnance. Une seule non remplie
            fait échouer le CFC, quelle que soit la moyenne générale : c'est
            ce que ce bloc rend visible.
        -->
        <section class="bg-card rounded-lg border p-4">
            <header class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-medium">Conditions de réussite</h2>

                <Badge :variant="isOnTrack ? 'secondary' : 'destructive'">
                    {{
                        isOnTrack
                            ? 'Sur la bonne voie'
                            : 'Conditions non remplies'
                    }}
                </Badge>
            </header>

            <Table class="mt-3">
                <TableHeader>
                    <TableRow>
                        <TableHead>Composante</TableHead>
                        <TableHead class="text-right">Pondération</TableHead>
                        <TableHead class="text-right">Note</TableHead>
                        <TableHead class="text-right">État</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow
                        v-for="requirement in apprentice.requirements"
                        :key="requirement.id"
                    >
                        <TableCell class="font-medium">
                            {{ requirement.label }}
                        </TableCell>
                        <TableCell class="text-right tabular-nums">
                            {{
                                requirement.weight === null
                                    ? '—'
                                    : `${requirement.weight} %`
                            }}
                        </TableCell>
                        <TableCell class="text-right tabular-nums">
                            {{ requirement.score.toFixed(1) }}
                        </TableCell>
                        <TableCell class="text-right">
                            {{
                                requirement.score >= scale.passing
                                    ? 'Atteint'
                                    : 'Insuffisant'
                            }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            <ChartCard
                title="Évolution de ma moyenne"
                :caption="`Le filet horizontal marque le seuil de ${scale.passing.toFixed(1)}.`"
            >
                <TrendLineChart :points="apprentice.timeline" :scale="scale" />

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
                                v-for="point in apprentice.timeline"
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

            <ChartCard
                title="Note par branche"
                :caption="`Échelle ${scale.min}–${scale.max}, seuil de réussite à ${scale.passing.toFixed(1)}.`"
            >
                <ScoreBarChart :items="barItems" :scale="scale" />

                <template #table>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Branche</TableHead>
                                <TableHead class="text-right">Note</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="branch in apprentice.branches"
                                :key="branch.id"
                            >
                                <TableCell>{{ branch.name }}</TableCell>
                                <TableCell
                                    class="text-right tabular-nums"
                                    :class="
                                        branch.score < scale.passing
                                            ? 'text-destructive'
                                            : ''
                                    "
                                >
                                    {{ branch.score.toFixed(1) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </template>
            </ChartCard>
        </div>
    </div>
</template>
