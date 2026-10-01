<script setup lang="ts">
import { ref } from 'vue';
import { fmt, fmtWeight, type Gradebook, PASS, plural } from '@/lib/gradebook';
import ChartBody from './ChartBody.vue';
import ChartCard from './ChartCard.vue';

const props = defineProps<{ gradebook: Gradebook }>();

const hover = ref<number | null>(null);
const ROW = 62;

function rows(width: number) {
    const iw = width - 40;
    const x = (v: number) => (v / 6) * iw;

    return {
        iw,
        x,
        height: props.gradebook.domains.length * ROW + 8,
        domains: props.gradebook.domains.map((id, i) => {
            const node = props.gradebook.node(id);

            return {
                id,
                i,
                top: i * ROW,
                name: props.gradebook.name(id),
                value: props.gradebook.nodeValue(id),
                weight: props.gradebook.weightOf(props.gradebook.root, id),
                count: props.gradebook.gradesUnder(id).length,
                rounding: node?.rounding_step ?? null,
            };
        }),
    };
}
</script>

<template>
    <ChartCard
        label-id="t-domains"
        title="Moyenne par domaine"
        description="Sur toute la formation · ligne pointillée = seuil 4.0"
    >
        <ChartBody v-slot="{ width, setTip }">
            <svg
                v-for="g in [rows(width)]"
                :key="width"
                :width="width"
                :height="g.height"
                :viewBox="`0 0 ${width} ${g.height}`"
                role="img"
                aria-label="Moyenne par domaine"
            >
                <g v-for="d in g.domains" :key="d.id">
                    <text x="0" :y="d.top + 14" class="c-label">
                        {{ d.name }}
                    </text>
                    <text
                        :x="width"
                        :y="d.top + 14"
                        :class="d.value === null ? 'c-sub' : 'c-value'"
                        text-anchor="end"
                    >
                        {{ d.value === null ? '—' : fmt(d.value) }}
                    </text>
                    <rect
                        x="0"
                        :y="d.top + 22"
                        :width="g.iw"
                        height="10"
                        rx="4"
                        class="c-bar-track"
                    />
                    <rect
                        v-if="d.value !== null"
                        x="0"
                        :y="d.top + 22"
                        :width="g.x(d.value)"
                        height="10"
                        rx="4"
                        :class="
                            d.value < PASS
                                ? 'c-bar c-bar--fail c-grow-x'
                                : 'c-bar c-grow-x'
                        "
                        :style="{
                            '--i': d.i,
                            opacity: hover === d.id ? 0.8 : undefined,
                        }"
                    />
                    <text x="0" :y="d.top + 48" class="c-sub">
                        Pondération {{ fmtWeight(d.weight) }} %{{
                            d.value === null ? ' · pas encore évalué' : ''
                        }}
                    </text>
                    <rect
                        x="0"
                        :y="d.top"
                        :width="width"
                        :height="ROW - 4"
                        class="c-hit"
                        @mouseenter="
                            hover = d.id;
                            setTip(
                                d.value === null
                                    ? {
                                          x: g.iw / 2,
                                          y: d.top + 20,
                                          value: 'Pas encore évalué',
                                          label: d.name,
                                          detail: `Pondération ${fmtWeight(d.weight)} %`,
                                      }
                                    : {
                                          x: g.x(d.value),
                                          y: d.top + 20,
                                          value: `${fmt(d.value)} / 6`,
                                          label: d.name,
                                          detail: `${plural(d.count, 'note')} · pondération ${fmtWeight(d.weight)} %${d.rounding ? ` · arrondi ${d.rounding}` : ''}`,
                                      },
                            );
                        "
                        @mouseleave="
                            hover = null;
                            setTip(null);
                        "
                    />
                </g>
                <line
                    :x1="g.x(PASS)"
                    :x2="g.x(PASS)"
                    y1="18"
                    :y2="g.height - 18"
                    class="c-threshold"
                />
            </svg>
        </ChartBody>
        <slot name="link" />
    </ChartCard>
</template>
