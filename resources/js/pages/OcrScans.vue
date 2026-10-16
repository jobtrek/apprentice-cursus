<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Donut, StackedBar } from '@unovis/ts';
import {
    VisAxis,
    VisDonut,
    VisSingleContainer,
    VisStackedBar,
    VisXYContainer,
} from '@unovis/vue';
import { CheckIcon, XIcon } from '@lucide/vue';
import { h, render, toRef } from 'vue';
import DataTable from '@/components/DataTable.vue';
import { PageContainer, PageHeader } from '@/components/page';
import PaginationNav from '@/components/PaginationNav.vue';
import { Badge } from '@/components/ui/badge';
import {
    type ChartConfig,
    ChartContainer,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import { TableCell, TableRow } from '@/components/ui/table';
import { usePagination } from '@/composables/usePagination';
import { cn } from '@/lib/utils';

type SpendDay = { day: string; gemini: number; jev: number };

/** Une tranche d'histogramme ; `warn` : la tranche à surveiller. */
type Bar = { label: string; count: number; warn: boolean };

type OcrScan = {
    id: number;
    created_at: string;
    user: string;
    subject: string;
    grade: string | null;
    confidence: number | null;
    flagged: boolean;
    pages_read: number;
    gemini_cost: number;
    jev_cost: number;
    duration_ms: number;
    saved_grade: string | null;
    /** Null tant qu'aucune note n'a été enregistrée depuis le scan. */
    grade_kept: boolean | null;
    subject_kept: boolean | null;
};

const props = defineProps<{
    /** Sur tous les scans ; null tant qu'aucun scan ne permet de le calculer. */
    stats: {
        scans: number;
        saved: number;
        flaggedRate: number | null;
        averageCost: number | null;
        averageDurationMs: number | null;
        /** Scans enregistrés et non signalés dont la note et la branche ont été gardées. */
        unflaggedAccuracy: number | null;
    };
    /** Agrégats pour les graphiques, sur tous les scans. */
    charts: {
        /** Les 30 derniers jours, jours sans scan compris. */
        spend: SpendDay[];
        /** Par tranche de 10 % de confiance. */
        confidence: Bar[];
        /** Par tranche de 2 s, la dernière = 30 s et plus. */
        duration: Bar[];
        /** Scans enregistrés seulement : le taux de correction. */
        accuracy: { correct: number; wrongGrade: number; wrongBranch: number };
    };
    /** Les 200 derniers scans. */
    scans: OcrScan[];
}>();

/** Les coûts par PDF tiennent en dixièmes de centime : 4 décimales. */
const usd = (value: number): string => `$${value.toFixed(value >= 1 ? 2 : 4)}`;

const percent = (rate: number | null): string =>
    rate === null ? '—' : `${Math.round(rate * 100)} %`;

const seconds = (ms: number | null): string =>
    ms === null ? '—' : `${(ms / 1000).toFixed(1)} s`;

const subjectLabel = (subject: string): string =>
    subject === 'OTHER' ? 'Autre' : subject.replace('subjects/', '');

/** Fuseau fixé : le rendu serveur (SSR) et le navigateur affichent la même heure. */
const dateLabel = (iso: string): string =>
    new Date(iso).toLocaleString('fr-CH', {
        dateStyle: 'short',
        timeStyle: 'short',
        timeZone: 'Europe/Zurich',
    });

/** Seuils de mise en production (docs/ocr_specs/service-spec.md). */
const GATES = [
    {
        label: 'Précision (non signalés)',
        value: percent(props.stats.unflaggedAccuracy),
        target: '≥ 98 %',
        met:
            props.stats.unflaggedAccuracy === null
                ? null
                : props.stats.unflaggedAccuracy >= 0.98,
    },
    {
        label: 'Part signalée',
        value: percent(props.stats.flaggedRate),
        target: '≤ 15 %',
        met:
            props.stats.flaggedRate === null
                ? null
                : props.stats.flaggedRate <= 0.15,
    },
    {
        label: 'Coût par PDF',
        value:
            props.stats.averageCost === null
                ? '—'
                : usd(props.stats.averageCost),
        target: '≤ $0.003',
        met:
            props.stats.averageCost === null
                ? null
                : props.stats.averageCost <= 0.003,
    },
    {
        label: 'Durée moyenne',
        value: seconds(props.stats.averageDurationMs),
        target: '≈ 10 s',
        met:
            props.stats.averageDurationMs === null
                ? null
                : props.stats.averageDurationMs <= 10_000,
    },
];

/**
 * Bulle au survol rendue par `ChartTooltipContent` : `title` en titre, puis
 * une ligne par clé de `config`, valeurs déjà formatées. Pas de
 * `componentToString` : son `useId()` côté client seulement décale les id
 * des graphiques entre le rendu serveur et l'hydratation.
 */
const tooltip =
    <T>(
        config: ChartConfig,
        toPayload: (d: T) => Record<string, string>,
        hideIndicator = true,
    ) =>
    (d: T): string => {
        const el = document.createElement('div');
        render(
            h(ChartTooltipContent, {
                config,
                payload: toPayload(d),
                labelKey: 'title',
                hideIndicator,
            }),
            el,
        );
        return el.innerHTML;
    };

const SPEND_CONFIG = {
    gemini: { label: 'Gemini', color: 'var(--chart-1)' },
    jev: { label: 'Jev', color: 'var(--chart-4)' },
    total: { label: 'Total' },
} satisfies ChartConfig;

/** Unovis 1.7 enveloppe la ligne d'une barre empilée : elle est sous `datum`. */
type Stacked<T> = { datum: T };

const spendTooltip = tooltip(
    SPEND_CONFIG,
    ({ datum: d }: Stacked<SpendDay>) => ({
        title: d.day.split('-').reverse().join('.'),
        gemini: usd(d.gemini),
        jev: usd(d.jev),
        total: usd(d.gemini + d.jev),
    }),
    false,
);

const spendTotal = usd(
    props.charts.spend.reduce((sum, d) => sum + d.gemini + d.jev, 0),
);

const dayLabel = (i: number | Date): string =>
    props.charts.spend[Number(i)]?.day
        .slice(5)
        .split('-')
        .reverse()
        .join('.') ?? '';

/** Un tick tous les 5 jours : des index entiers, jamais 2.5. */
const SPEND_TICKS = props.charts.spend
    .map((_, i) => i)
    .filter((i) => i % 5 === 4);

const warned = (bars: Bar[]): number =>
    bars.reduce((sum, bar) => sum + (bar.warn ? bar.count : 0), 0);

const HISTOGRAMS = [
    {
        title: 'Confiance',
        bars: props.charts.confidence,
        hint: `Scans par tranche ; ${warned(props.charts.confidence)} sous le seuil de 50 % (en orange)`,
    },
    {
        title: 'Durée',
        bars: props.charts.duration,
        hint: `Scans par tranche de 2 s ; ${warned(props.charts.duration)} à 30 s et plus (en orange)`,
    },
];

/** Deux séries exclusives : chaque barre prend la couleur de celle qui est non nulle. */
const HISTOGRAM_Y = [
    (d: Bar) => (d.warn ? 0 : d.count),
    (d: Bar) => (d.warn ? d.count : 0),
];
const HISTOGRAM_COLORS = ['var(--chart-1)', 'var(--warning)'];

const histogramTooltip = tooltip(
    { count: { label: 'Scans' } },
    ({ datum: d }: Stacked<Bar>) => ({
        title: d.label,
        count: String(d.count),
    }),
);

const { accuracy } = props.charts;

const ACCURACY = [
    { key: 'correct', label: 'Correct', color: 'var(--success)' },
    { key: 'wrongGrade', label: 'Note corrigée', color: 'var(--destructive)' },
    { key: 'wrongBranch', label: 'Branche corrigée', color: 'var(--warning)' },
].map((slice) => ({
    ...slice,
    count: accuracy[slice.key as keyof typeof accuracy],
}));

type Slice = (typeof ACCURACY)[number];

/** Le libellé de légende porte le nombre : elle se lit sans les couleurs. */
const ACCURACY_CONFIG: ChartConfig = Object.fromEntries(
    ACCURACY.map((d) => [
        d.key,
        { label: `${d.label} · ${d.count}`, color: d.color },
    ]),
);

const share = (count: number): string =>
    percent(props.stats.saved ? count / props.stats.saved : null);

/** Un segment du donut porte sa ligne sous `data` (arc d3). */
const accuracyTooltip = tooltip(
    { count: { label: 'Scans' }, share: { label: 'Part' } },
    ({ data: d }: { data: Slice }) => ({
        title: d.label,
        count: String(d.count),
        share: share(d.count),
    }),
);

const COLUMNS = [
    { key: 'date', label: 'Date' },
    { key: 'user', label: 'Apprenti·e' },
    { key: 'subject', label: 'Matière' },
    { key: 'grade', label: 'Note' },
    { key: 'confidence', label: 'Confiance' },
    { key: 'cost', label: 'Coût' },
    { key: 'duration', label: 'Durée' },
];

const {
    currentPage,
    pageCount,
    pageItems: pageScans,
} = usePagination(toRef(props, 'scans'));
</script>

<template>
    <Head title="OCR" />

    <PageContainer size="lg">
        <PageHeader
            title="OCR"
            description="Coût et précision de la lecture automatique des notes."
        />

        <template v-if="stats.scans">
            <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="gate in GATES"
                    :key="gate.label"
                    class="bg-card flex flex-col gap-1 rounded-xl border p-4 shadow-xs"
                >
                    <dt class="text-muted-foreground text-xs">
                        {{ gate.label }}
                    </dt>
                    <dd class="text-2xl font-semibold tabular-nums">
                        {{ gate.value }}
                    </dd>
                    <dd
                        :class="
                            cn(
                                'text-xs',
                                gate.met === true && 'text-success',
                                gate.met === false && 'text-destructive',
                                gate.met === null && 'text-muted-foreground',
                            )
                        "
                    >
                        Objectif {{ gate.target }}
                        <template v-if="gate.met !== null">
                            · {{ gate.met ? 'atteint' : 'non atteint' }}
                        </template>
                    </dd>
                </div>
            </dl>

            <div class="grid gap-3 lg:grid-cols-2">
                <section
                    class="bg-card flex flex-col gap-3 rounded-xl border p-4 shadow-xs"
                >
                    <header>
                        <h2 class="text-sm font-medium">Dépenses (30 jours)</h2>
                        <p class="text-muted-foreground text-xs">
                            Par jour, Gemini et Jev empilés ; {{ spendTotal }}
                            au total
                        </p>
                    </header>
                    <ChartContainer
                        :config="SPEND_CONFIG"
                        class="aspect-auto h-48"
                        role="img"
                        :aria-label="`Dépenses OCR par jour sur 30 jours, ${spendTotal} au total`"
                    >
                        <VisXYContainer :data="charts.spend">
                            <VisStackedBar
                                :x="(_: SpendDay, i: number) => i"
                                :y="[
                                    (d: SpendDay) => d.gemini,
                                    (d: SpendDay) => d.jev,
                                ]"
                                :color="[
                                    SPEND_CONFIG.gemini.color,
                                    SPEND_CONFIG.jev.color,
                                ]"
                                :rounded-corners="2"
                            />
                            <VisAxis
                                type="x"
                                :tick-values="SPEND_TICKS"
                                :tick-format="dayLabel"
                                :grid-line="false"
                                :domain-line="false"
                            />
                            <VisAxis
                                type="y"
                                :num-ticks="4"
                                :tick-format="
                                    (v: number | Date) => usd(Number(v))
                                "
                                :domain-line="false"
                            />
                            <ChartTooltip
                                :triggers="{
                                    [StackedBar.selectors.bar]: spendTooltip,
                                }"
                            />
                        </VisXYContainer>
                    </ChartContainer>
                </section>

                <section
                    class="bg-card flex flex-col gap-3 rounded-xl border p-4 shadow-xs"
                >
                    <header>
                        <h2 class="text-sm font-medium">Précision</h2>
                        <p class="text-muted-foreground text-xs">
                            Ce que l'apprenti·e a gardé de la lecture, sur
                            {{ stats.saved }} scans enregistrés ;
                            {{ stats.scans - stats.saved }} en attente
                        </p>
                    </header>
                    <ChartContainer
                        :config="ACCURACY_CONFIG"
                        class="aspect-auto flex-row justify-start gap-6 [--vis-donut-central-label-text-color:var(--foreground)] [--vis-donut-central-sub-label-text-color:var(--muted-foreground)]"
                    >
                        <div
                            class="size-48 shrink-0"
                            role="img"
                            :aria-label="`${share(accuracy.correct)} des scans enregistrés gardés tels quels`"
                        >
                            <VisSingleContainer :data="ACCURACY">
                                <VisDonut
                                    :value="(d: Slice) => d.count"
                                    :color="(d: Slice) => d.color"
                                    :arc-width="24"
                                    :central-label="share(accuracy.correct)"
                                    central-sub-label="correctes"
                                />
                                <ChartTooltip
                                    :triggers="{
                                        [Donut.selectors.segment]:
                                            accuracyTooltip,
                                    }"
                                />
                            </VisSingleContainer>
                        </div>
                        <ChartLegendContent
                            class="flex-col items-start gap-2 pt-0 text-sm"
                        />
                    </ChartContainer>
                </section>

                <section
                    v-for="histogram in HISTOGRAMS"
                    :key="histogram.title"
                    class="bg-card flex flex-col gap-3 rounded-xl border p-4 shadow-xs"
                >
                    <header>
                        <h2 class="text-sm font-medium">
                            {{ histogram.title }}
                        </h2>
                        <p class="text-muted-foreground text-xs">
                            {{ histogram.hint }}
                        </p>
                    </header>
                    <ChartContainer
                        :config="{}"
                        class="aspect-auto h-48"
                        role="img"
                        :aria-label="`${histogram.title} : ${histogram.hint}`"
                    >
                        <VisXYContainer :data="histogram.bars">
                            <VisStackedBar
                                :x="(_: Bar, i: number) => i"
                                :y="HISTOGRAM_Y"
                                :color="HISTOGRAM_COLORS"
                                :rounded-corners="2"
                            />
                            <VisAxis
                                type="x"
                                :tick-values="
                                    histogram.bars
                                        .map((_, i) => i)
                                        .filter((i) => i % 2 === 0)
                                "
                                :tick-format="
                                    (i: number | Date) =>
                                        histogram.bars[Number(i)]?.label ?? ''
                                "
                                :grid-line="false"
                                :domain-line="false"
                            />
                            <VisAxis
                                type="y"
                                :num-ticks="4"
                                :domain-line="false"
                            />
                            <ChartTooltip
                                :triggers="{
                                    [StackedBar.selectors.bar]:
                                        histogramTooltip,
                                }"
                            />
                        </VisXYContainer>
                    </ChartContainer>
                </section>
            </div>
        </template>

        <DataTable
            :columns="COLUMNS"
            :data="pageScans"
            empty-message="Aucun PDF analysé pour l'instant."
        >
            <template #row="{ item: scan }">
                <TableRow :key="scan.id">
                    <TableCell class="text-muted-foreground tabular-nums">
                        {{ dateLabel(scan.created_at) }}
                    </TableCell>
                    <TableCell class="font-medium">{{ scan.user }}</TableCell>
                    <TableCell>
                        <span class="flex items-center gap-1.5">
                            {{ subjectLabel(scan.subject) }}
                            <template v-if="scan.subject_kept !== null">
                                <CheckIcon
                                    v-if="scan.subject_kept"
                                    class="text-success size-4"
                                    aria-label="Branche gardée"
                                />
                                <XIcon
                                    v-else
                                    class="text-destructive size-4"
                                    aria-label="Branche corrigée"
                                />
                            </template>
                        </span>
                    </TableCell>
                    <TableCell class="tabular-nums">
                        <span class="flex items-center gap-1.5">
                            {{ scan.grade ?? '—' }}
                            <template v-if="scan.grade_kept !== null">
                                <CheckIcon
                                    v-if="scan.grade_kept"
                                    class="text-success size-4"
                                    aria-label="Note gardée"
                                />
                                <span v-else class="text-destructive">
                                    → {{ scan.saved_grade }}
                                </span>
                            </template>
                            <Badge v-if="scan.flagged" variant="outline">
                                Signalé
                            </Badge>
                        </span>
                    </TableCell>
                    <TableCell class="tabular-nums">
                        {{ percent(scan.confidence) }}
                    </TableCell>
                    <TableCell
                        class="tabular-nums"
                        :title="`Gemini ${usd(scan.gemini_cost)} (${scan.pages_read} p.) · Jev ${usd(scan.jev_cost)}`"
                    >
                        {{ usd(scan.gemini_cost + scan.jev_cost) }}
                    </TableCell>
                    <TableCell class="tabular-nums">
                        {{ seconds(scan.duration_ms) }}
                    </TableCell>
                </TableRow>
            </template>
        </DataTable>

        <PaginationNav v-model="currentPage" :page-count="pageCount" />
    </PageContainer>
</template>
