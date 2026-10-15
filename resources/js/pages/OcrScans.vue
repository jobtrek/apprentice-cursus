<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    CheckIcon,
    CoinsIcon,
    FlagIcon,
    ReceiptIcon,
    TargetIcon,
    TimerIcon,
    XIcon,
} from '@lucide/vue';
import type { Component } from 'vue';
import DataTable from '@/components/DataTable.vue';
import IconTile from '@/components/IconTile.vue';
import { PageContainer, PageHeader } from '@/components/page';
import { Badge } from '@/components/ui/badge';
import { TableCell, TableRow } from '@/components/ui/table';
import { TONES, type Tone } from '@/lib/apprentice';

type OcrScan = {
    id: number;
    created_at: string;
    user: string;
    /** Libellé de la matière, déjà prêt à afficher (« Anglais », « Autre »). */
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
    /** Sur tous les scans ; les taux sont entre 0 et 1, null sans données. */
    stats: {
        scans: number;
        saved: number;
        geminiCost: number;
        jevCost: number;
        monthCost: number;
        averageCost: number | null;
        flaggedRate: number | null;
        averageDurationMs: number | null;
        gradeAccuracy: number | null;
        subjectAccuracy: number | null;
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

/** Fuseau fixé : le rendu serveur (SSR) et le navigateur affichent la même heure. */
const dateLabel = (iso: string): string =>
    new Date(iso).toLocaleString('fr-CH', {
        dateStyle: 'short',
        timeStyle: 'short',
        timeZone: 'Europe/Zurich',
    });

const s = props.stats;

const TILES: {
    label: string;
    value: string;
    hint: string;
    icon: Component;
    tone: Tone;
}[] = [
    {
        label: 'Dépenses ce mois',
        value: usd(s.monthCost),
        hint: `${s.scans} scans au total`,
        icon: CoinsIcon,
        tone: 'primary',
    },
    {
        label: 'Coût moyen par PDF',
        value: s.averageCost === null ? '—' : usd(s.averageCost),
        hint: `Gemini ${usd(s.geminiCost)} · Jev ${usd(s.jevCost)}`,
        icon: ReceiptIcon,
        tone: 'primary',
    },
    {
        label: 'Précision',
        value: `${percent(s.gradeAccuracy)} · ${percent(s.subjectAccuracy)}`,
        hint: `Note · branche, sur ${s.saved} notes enregistrées`,
        icon: TargetIcon,
        tone: 'success',
    },
    {
        label: 'Signalés',
        value: percent(s.flaggedRate),
        hint: 'Sans note ou confiance < 50 %',
        icon: FlagIcon,
        tone: 'warning',
    },
    {
        label: 'Durée moyenne',
        value: seconds(s.averageDurationMs),
        hint: 'Du dépôt au résultat',
        icon: TimerIcon,
        tone: 'muted',
    },
];

const COLUMNS = [
    { key: 'date', label: 'Date' },
    { key: 'user', label: 'Apprenti·e' },
    { key: 'subject', label: 'Matière' },
    { key: 'grade', label: 'Note' },
    { key: 'confidence', label: 'Confiance' },
    { key: 'cost', label: 'Coût' },
    { key: 'duration', label: 'Durée' },
];
</script>

<template>
    <Head title="OCR" />

    <PageContainer size="lg">
        <PageHeader
            title="OCR"
            description="Coût et précision de la lecture automatique des notes."
        />

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            <div
                v-for="tile in TILES"
                :key="tile.label"
                class="bg-card flex flex-col gap-4 rounded-xl border p-4 shadow-xs"
            >
                <IconTile :class="TONES[tile.tone].text">
                    <component :is="tile.icon" />
                </IconTile>
                <span class="flex flex-col gap-0.5">
                    <span
                        class="text-2xl font-semibold tracking-tight tabular-nums"
                    >
                        {{ tile.value }}
                    </span>
                    <span class="text-sm font-medium">{{ tile.label }}</span>
                    <span class="text-muted-foreground text-xs">
                        {{ tile.hint }}
                    </span>
                </span>
            </div>
        </div>

        <DataTable
            :columns="COLUMNS"
            :data="scans"
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
                            {{ scan.subject }}
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
    </PageContainer>
</template>
