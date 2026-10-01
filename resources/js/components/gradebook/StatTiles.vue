<script setup lang="ts">
import { computed } from 'vue';
import { fmt, type Gradebook, PASS, shortName } from '@/lib/gradebook';
import CountUp from './CountUp.vue';
import type { Grade } from '@/types/grade';
import StatusChip from './StatusChip.vue';

const props = defineProps<{ gradebook: Gradebook; grades: Grade[] }>();

const final = computed(() => props.gradebook.nodeValue(props.gradebook.root));

/** Domaines pas encore notés : la note finale est provisoire sans eux. */
const missing = computed(() =>
    props.gradebook.domains
        .filter((id) => props.gradebook.nodeValue(id) === null)
        .map((id) => shortName(props.gradebook.name(id))),
);

/** Dernier semestre noté, comparé au semestre noté précédent. */
const semesters = computed(() =>
    [...new Set(props.grades.map((grade) => grade.semester))].sort(
        (a, b) => a - b,
    ),
);
const current = computed(() => semesters.value.at(-1) ?? null);
const previous = computed(() => semesters.value.at(-2) ?? null);

const semesterValue = (semester: number | null) =>
    semester === null
        ? null
        : props.gradebook.nodeValue(
              props.gradebook.root,
              props.gradebook.inSemester(semester),
          );

const currentValue = computed(() => semesterValue(current.value));
const delta = computed(() => {
    const prev = semesterValue(previous.value);

    return currentValue.value === null || prev === null
        ? null
        : currentValue.value - prev;
});

const inCurrent = computed(() =>
    current.value === null
        ? 0
        : props.gradebook.inSemester(current.value).length,
);
const failed = computed(
    () => props.grades.filter((grade) => grade.value < PASS).length,
);
</script>

<template>
    <section class="stat-grid" aria-label="Statistiques">
        <div class="stat">
            <span class="stat__label">Note finale CFC</span>
            <span class="stat__value">
                <template v-if="final !== null">
                    <CountUp :value="final" :format="fmt" /> <small>/ 6</small>
                </template>
                <template v-else>—</template>
            </span>
            <span class="stat__foot">
                <template v-if="final !== null">
                    <StatusChip :value="final" />
                    provisoire{{
                        missing.length ? `, sans ${missing.join(', ')}` : ''
                    }}
                </template>
                <template v-else>Aucune note pour l'instant</template>
            </span>
        </div>

        <div class="stat">
            <span class="stat__label">
                {{
                    current === null
                        ? 'Moyenne du semestre'
                        : `Moyenne du semestre ${current}`
                }}
            </span>
            <span class="stat__value">
                <CountUp
                    v-if="currentValue !== null"
                    :value="currentValue"
                    :format="fmt"
                    :delay="60"
                />
                <template v-else>—</template>
            </span>
            <span class="stat__foot">
                <template v-if="delta === null">
                    {{ previous === null ? 'premier semestre noté' : '—' }}
                </template>
                <template v-else-if="Math.abs(delta) < 0.05">
                    stable par rapport au semestre {{ previous }}
                </template>
                <template v-else>
                    <span :class="delta > 0 ? 'delta-up' : 'delta-down'">
                        {{ (delta > 0 ? '+' : '−') + fmt(Math.abs(delta)) }}
                    </span>
                    vs semestre {{ previous }}
                </template>
            </span>
        </div>

        <div class="stat">
            <span class="stat__label">Notes saisies</span>
            <span class="stat__value"
                ><CountUp :value="grades.length" :delay="120"
            /></span>
            <span class="stat__foot">
                {{
                    current === null
                        ? 'aucune pour l’instant'
                        : `${inCurrent} au semestre ${current}`
                }}
            </span>
        </div>

        <div class="stat">
            <span class="stat__label">Notes insuffisantes</span>
            <span class="stat__value"
                ><CountUp :value="failed" :delay="180"
            /></span>
            <span class="stat__foot"
                >sur {{ grades.length }} notes, seuil 4.0</span
            >
        </div>
    </section>
</template>
