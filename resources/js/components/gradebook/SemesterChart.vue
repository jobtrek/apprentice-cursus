<script setup lang="ts">
import { computed, ref } from 'vue';
import {
    barPath,
    domainLabel,
    fmt,
    type Gradebook,
    PASS,
    plural,
    SEMESTERS,
} from '@/lib/gradebook';
import ChartBody from './ChartBody.vue';
import ChartCard from './ChartCard.vue';
import Segmented from './Segmented.vue';

const props = defineProps<{
    gradebook: Gradebook;
    /** Semestre en cours : les suivants sont dessinés en pointillé. */
    currentSemester: number;
}>();

const filter = ref(props.gradebook.root);
const hover = ref<number | null>(null);

// Le TPI n'a qu'une note de fin de cursus : pas de moyenne par semestre.
const options = computed(() => [
    { value: props.gradebook.root, label: 'Toutes' },
    ...props.gradebook.domains
        .filter((id) => props.gradebook.hasSemesterLeaves(id))
        .map((id) => ({
            value: id,
            label: domainLabel(props.gradebook.name(id)),
        })),
]);

const H = 260;
const m = { t: 20, r: 8, b: 44, l: 28 };

function layout(width: number) {
    const iw = width - m.l - m.r;
    const ih = H - m.t - m.b;
    const slot = iw / SEMESTERS;
    const bw = Math.min(44, slot - 12);
    const y = (v: number) => m.t + (1 - v / 6) * ih;
    const leaves = props.gradebook.leavesOf(filter.value);

    const bars = Array.from({ length: SEMESTERS }, (_, i) => {
        const s = i + 1;
        const cx = m.l + slot * i + slot / 2;
        const grades = props.gradebook
            .inSemester(s)
            .filter((grade) => leaves.has(grade.node_id));
        const value = props.gradebook.nodeValue(filter.value, grades);

        return { s, cx, grades, value, future: s > props.currentSemester };
    });

    return { iw, ih, slot, bw, y, bars };
}
</script>

<template>
    <ChartCard
        label-id="t-semesters"
        title="Moyenne par semestre"
        description="Semestres 1 à 8 de la formation, arrondis selon le domaine"
    >
        <template #aside>
            <Segmented
                v-model="filter"
                :options="options"
                label="Domaine affiché"
            />
        </template>

        <ChartBody v-slot="{ width, setTip }">
            <svg
                v-for="g in [layout(width)]"
                :key="width"
                :width="width"
                :height="H"
                :viewBox="`0 0 ${width} ${H}`"
                role="img"
                aria-label="Moyenne par semestre"
            >
                <g v-for="v in [0, 2, 4, 6]" :key="`grid-${v}`">
                    <line
                        :x1="m.l"
                        :x2="width - m.r"
                        :y1="g.y(v)"
                        :y2="g.y(v)"
                        class="c-grid"
                    />
                    <text
                        :x="m.l - 8"
                        :y="g.y(v) + 4"
                        class="c-axis"
                        text-anchor="end"
                    >
                        {{ v }}
                    </text>
                </g>

                <g v-for="bar in g.bars" :key="bar.s">
                    <text
                        :x="bar.cx"
                        :y="H - 24"
                        :class="
                            bar.s === currentSemester
                                ? 'c-label c-strong'
                                : bar.future
                                  ? 'c-axis c-faint'
                                  : 'c-axis'
                        "
                        text-anchor="middle"
                    >
                        S{{ bar.s }}
                    </text>
                    <text
                        v-if="bar.s % 2 === 1"
                        :x="bar.cx + g.slot / 2"
                        :y="H - 6"
                        class="c-sub"
                        text-anchor="middle"
                    >
                        Année {{ (bar.s + 1) / 2 }}
                    </text>

                    <rect
                        v-if="bar.future"
                        :x="bar.cx - g.bw / 2"
                        :y="m.t"
                        :width="g.bw"
                        :height="g.ih"
                        rx="4"
                        class="c-slot"
                    />
                    <text
                        v-else-if="bar.value === null"
                        :x="bar.cx"
                        :y="g.y(0) - 8"
                        class="c-sub"
                        text-anchor="middle"
                    >
                        —
                    </text>
                    <template v-else>
                        <!-- Clé par filtre : les barres repoussent quand le domaine change. -->
                        <path
                            :key="`bar-${filter}`"
                            :d="
                                barPath(
                                    bar.cx - g.bw / 2,
                                    bar.cx + g.bw / 2,
                                    g.y(bar.value),
                                    g.y(0),
                                )
                            "
                            :class="
                                bar.value < PASS
                                    ? 'c-bar c-bar--fail c-grow-y'
                                    : 'c-bar c-grow-y'
                            "
                            :style="{
                                '--i': bar.s - 1,
                                opacity: hover === bar.s ? 0.8 : undefined,
                            }"
                        />
                        <text
                            :key="`val-${filter}`"
                            :x="bar.cx"
                            :y="g.y(bar.value) - 6"
                            class="c-value c-fade"
                            text-anchor="middle"
                            :style="{ '--i': bar.s - 1 }"
                        >
                            {{ fmt(bar.value) }}
                        </text>
                        <rect
                            :x="bar.cx - g.slot / 2"
                            y="0"
                            :width="g.slot"
                            :height="H - 30"
                            class="c-hit"
                            @mouseenter="
                                hover = bar.s;
                                setTip({
                                    x: bar.cx,
                                    y: g.y(bar.value),
                                    value: `${fmt(bar.value)} / 6`,
                                    label: `Semestre ${bar.s}${bar.s === currentSemester ? ' (en cours)' : ''}`,
                                    detail: `${plural(bar.grades.length, 'note')} · ${gradebook.name(filter)}`,
                                });
                            "
                            @mouseleave="
                                hover = null;
                                setTip(null);
                            "
                        />
                    </template>
                </g>

                <line
                    :x1="m.l"
                    :x2="width - m.r"
                    :y1="g.y(PASS)"
                    :y2="g.y(PASS)"
                    class="c-threshold"
                />
                <text
                    :x="width - m.r"
                    :y="g.y(PASS) - 6"
                    class="c-threshold-label"
                    text-anchor="end"
                >
                    Seuil 4.0
                </text>
            </svg>
        </ChartBody>
    </ChartCard>
</template>
