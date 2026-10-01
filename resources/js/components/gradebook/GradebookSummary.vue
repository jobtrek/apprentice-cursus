<script setup lang="ts">
import { computed } from 'vue';
import {
    fmt,
    fmtWeight,
    type Gradebook,
    PASS,
    plural,
    shortName,
} from '@/lib/gradebook';
import CountUp from './CountUp.vue';
import StatusChip from './StatusChip.vue';

const props = defineProps<{ gradebook: Gradebook }>();

const final = computed(() => props.gradebook.nodeValue(props.gradebook.root));

/** Jauge à 270°, avec un trait au seuil de réussite. */
const R = 52;
const C = 2 * Math.PI * R;
const ARC = C * 0.75;
const fill = computed(() =>
    final.value === null
        ? 0
        : ARC * Math.max(0, Math.min(1, (final.value - 1) / 5)),
);
const tickAngle = ((135 + (270 * (PASS - 1)) / 5) * Math.PI) / 180;

const domains = computed(() =>
    props.gradebook.domains.map((id, i) => ({
        id,
        i,
        name: props.gradebook.name(id),
        weight: props.gradebook.weightOf(props.gradebook.root, id),
        value: props.gradebook.nodeValue(id),
        count: props.gradebook.gradesUnder(id).length,
    })),
);

const missing = computed(() =>
    domains.value.filter((domain) => domain.value === null),
);
</script>

<template>
    <section class="gb-summary" aria-label="Moyennes">
        <article class="gb-final">
            <div class="gb-final__head">
                <h2 class="gb-card-title">Note finale CFC</h2>
                <StatusChip v-if="final !== null" :value="final" />
            </div>
            <div class="gb-final__gauge">
                <svg class="gb-gauge" viewBox="0 0 128 128" aria-hidden="true">
                    <circle
                        cx="64"
                        cy="64"
                        :r="R"
                        class="gb-gauge__track"
                        :stroke-dasharray="`${ARC} ${C}`"
                        transform="rotate(135 64 64)"
                    />
                    <circle
                        v-if="final !== null"
                        cx="64"
                        cy="64"
                        :r="R"
                        class="gb-gauge__fill"
                        :stroke-dasharray="`${fill} ${C}`"
                        transform="rotate(135 64 64)"
                    />
                    <line
                        :x1="64 + 44 * Math.cos(tickAngle)"
                        :y1="64 + 44 * Math.sin(tickAngle)"
                        :x2="64 + 60 * Math.cos(tickAngle)"
                        :y2="64 + 60 * Math.sin(tickAngle)"
                        class="gb-gauge__tick"
                    />
                </svg>
                <div class="gb-final__value">
                    <span>
                        <CountUp
                            v-if="final !== null"
                            :value="final"
                            :format="fmt"
                        />
                        <template v-else>—</template>
                    </span>
                    <small>sur 6</small>
                </div>
            </div>
            <p class="gb-final__note">
                Provisoire : moyenne pondérée des domaines déjà notés.
                <template v-for="domain in missing" :key="domain.id">
                    {{ shortName(domain.name) }} compte pour
                    {{ fmtWeight(domain.weight) }} % une fois évalué.
                </template>
            </p>
        </article>

        <div class="gb-domains">
            <article
                v-for="domain in domains"
                :key="domain.id"
                class="gb-domain"
            >
                <div class="gb-domain__head">
                    <h3 class="gb-domain__name">{{ domain.name }}</h3>
                    <span class="gb-weight"
                        >{{ fmtWeight(domain.weight) }} %</span
                    >
                </div>
                <template v-if="domain.value === null">
                    <p class="gb-domain__value is-empty">—</p>
                    <div class="gb-meter"><span class="gb-meter__tick" /></div>
                    <p class="gb-domain__foot">Pas encore évalué</p>
                </template>
                <template v-else>
                    <p class="gb-domain__value">
                        <CountUp :value="domain.value" :format="fmt" />
                    </p>
                    <div
                        class="gb-meter"
                        role="img"
                        :aria-label="`${fmt(domain.value)} sur 6`"
                    >
                        <span
                            :class="
                                domain.value < PASS
                                    ? 'gb-meter__bar is-fail'
                                    : 'gb-meter__bar'
                            "
                            :style="{
                                width: `${(domain.value / 6) * 100}%`,
                                '--i': domain.i,
                            }"
                        />
                        <span class="gb-meter__tick" />
                    </div>
                    <p class="gb-domain__foot">
                        <StatusChip :value="domain.value" />
                        {{ plural(domain.count, 'note') }}
                    </p>
                </template>
            </article>
        </div>
    </section>
</template>
