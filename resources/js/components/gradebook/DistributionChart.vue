<script setup lang="ts">
import { barPath, fmt, PASS, plural } from '@/lib/gradebook';
import type { Grade } from '@/types/grade';
import ChartBody from './ChartBody.vue';
import ChartCard from './ChartCard.vue';

const props = defineProps<{ grades: Grade[] }>();

const H = 240;
const m = { t: 20, r: 4, b: 26, l: 4 };

function histogram(width: number) {
    // Part de 3.0 sauf note plus basse : les tranches vides n'écrasent pas le graphique.
    const lowest = Math.min(...props.grades.map((grade) => grade.value));
    const from = Math.min(3, Math.floor(lowest));
    const bins: { lo: number; hi: number; n: number }[] = [];

    for (let lo = from; lo < 6; lo += 0.5) {
        bins.push({
            lo,
            hi: lo + 0.5,
            n: props.grades.filter(
                (grade) =>
                    grade.value >= lo &&
                    (grade.value < lo + 0.5 ||
                        (lo === 5.5 && grade.value === 6)),
            ).length,
        });
    }

    const maxN = Math.max(1, ...bins.map((bin) => bin.n));
    const iw = width - m.l - m.r;
    const ih = H - m.t - m.b;
    const slot = iw / bins.length;
    const base = m.t + ih;

    return {
        base,
        slot,
        from,
        ticks: Array.from({ length: 6 - from + 1 }, (_, i) => from + i),
        px: m.l + (PASS - from) * 2 * slot,
        bins: bins.map((bin, i) => {
            const x0 = m.l + slot * i + 1;
            const x1 = x0 + slot - 2;
            const top = base - (bin.n / maxN) * ih;

            return {
                ...bin,
                i,
                x0,
                x1,
                top,
                cx: (x0 + x1) / 2,
                fail: bin.hi <= PASS,
            };
        }),
    };
}
</script>

<template>
    <ChartCard
        label-id="t-dist"
        title="Répartition des notes"
        description="Toutes les notes, par tranche de 0.5 point"
    >
        <template #aside>
            <div class="legend">
                <span><i class="sq" />4.0 et plus</span>
                <span><i class="sq sq--fail" />Insuffisante (&lt; 4.0)</span>
            </div>
        </template>

        <p v-if="grades.length === 0" class="text-muted-foreground text-sm">
            Aucune note pour l'instant.
        </p>
        <ChartBody v-else v-slot="{ width, setTip }">
            <svg
                v-for="g in [histogram(width)]"
                :key="width"
                :width="width"
                :height="H"
                :viewBox="`0 0 ${width} ${H}`"
                role="img"
                aria-label="Répartition des notes"
            >
                <g v-for="bin in g.bins" :key="bin.lo">
                    <template v-if="bin.n > 0">
                        <path
                            :d="barPath(bin.x0, bin.x1, bin.top, g.base)"
                            :class="
                                bin.fail
                                    ? 'c-bar c-bar--fail c-grow-y'
                                    : 'c-bar c-grow-y'
                            "
                            :style="{ '--i': bin.i }"
                        />
                        <text
                            :x="bin.cx"
                            :y="bin.top - 6"
                            class="c-value c-fade"
                            text-anchor="middle"
                            :style="{ '--i': bin.i }"
                        >
                            {{ bin.n }}
                        </text>
                    </template>
                    <rect
                        :x="bin.x0"
                        y="0"
                        :width="g.slot"
                        :height="H"
                        class="c-hit"
                        @mouseenter="
                            setTip({
                                x: bin.cx,
                                y: bin.n ? bin.top : g.base,
                                value: plural(bin.n, 'note'),
                                detail: `entre ${fmt(bin.lo)} et ${bin.lo === 5.5 ? '6.0' : fmt(bin.hi - 0.1)}${bin.fail ? ' · insuffisante' : ''}`,
                            })
                        "
                        @mouseleave="setTip(null)"
                    />
                </g>
                <line
                    :x1="m.l"
                    :x2="width - m.r"
                    :y1="g.base"
                    :y2="g.base"
                    class="c-grid"
                />
                <text
                    v-for="t in g.ticks"
                    :key="t"
                    :x="m.l + (t - g.from) * 2 * g.slot"
                    :y="H - 8"
                    class="c-axis"
                    :text-anchor="
                        t === g.from ? 'start' : t === 6 ? 'end' : 'middle'
                    "
                >
                    {{ t }}.0
                </text>
                <line
                    :x1="g.px"
                    :x2="g.px"
                    :y1="m.t - 8"
                    :y2="g.base"
                    class="c-threshold"
                />
            </svg>
        </ChartBody>
    </ChartCard>
</template>
