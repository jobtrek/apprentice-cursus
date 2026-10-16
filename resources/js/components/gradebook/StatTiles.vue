<script setup lang="ts">
import {
    CalendarRangeIcon,
    FileTextIcon,
    GraduationCapIcon,
    TriangleAlertIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import IconTile from '@/components/IconTile.vue';
import { fmt, type Averages, shortName } from '@/lib/gradebook';
import { PASSING_GRADE } from '@/lib/grade-scale';
import type { Grade } from '@/types/grade';
import StatusChip from './StatusChip.vue';

const props = defineProps<{ averages: Averages; grades: Grade[] }>();

const final = computed(() => props.averages.final);

/** Domaines pas encore notés : la note finale est provisoire sans eux. */
const missing = computed(() =>
    props.averages.domains
        .filter((domain) => domain.average === null)
        .map((domain) => shortName(domain.name)),
);

/** Dernier semestre noté, comparé au semestre noté précédent. */
const semesters = computed(() =>
    [...new Set(props.grades.map((grade) => grade.semester))].sort(
        (a, b) => a - b,
    ),
);
const current = computed(() => semesters.value.at(-1) ?? null);
const previous = computed(() => semesters.value.at(-2) ?? null);

const semesterOf = (semester: number | null) =>
    props.averages.semesters.find((entry) => entry.semester === semester);
const semesterValue = (semester: number | null) =>
    semesterOf(semester)?.average ?? null;

const currentValue = computed(() => semesterValue(current.value));
const delta = computed(() => {
    const prev = semesterValue(previous.value);

    return currentValue.value === null || prev === null
        ? null
        : currentValue.value - prev;
});

const inCurrent = computed(() => semesterOf(current.value)?.count ?? 0);
const failed = computed(
    () => props.grades.filter((grade) => grade.value < PASSING_GRADE).length,
);
</script>

<template>
    <section class="stat-grid" aria-label="Statistiques">
        <div class="stat">
            <span class="flex items-start justify-between gap-2">
                <span class="stat__label">Note finale CFC</span>
                <IconTile size="sm">
                    <GraduationCapIcon />
                </IconTile>
            </span>
            <span class="stat__value">
                <template v-if="final !== null">
                    {{ fmt(final) }} <small>/ 6</small>
                </template>
                <template v-else>—</template>
            </span>
            <span class="stat__foot">
                <template v-if="final !== null">
                    <StatusChip :value="final" />
                    <!-- Provisoire tant qu'une évaluation comptée n'a pas de note. -->
                    <template v-if="!averages.complete">
                        provisoire{{
                            missing.length ? `, sans ${missing.join(', ')}` : ''
                        }}
                    </template>
                    <template v-else>toutes les évaluations notées</template>
                </template>
                <template v-else>Aucune note pour l'instant</template>
            </span>
        </div>

        <div class="stat">
            <span class="flex items-start justify-between gap-2">
                <span class="stat__label">
                    {{
                        current === null
                            ? 'Moyenne du semestre'
                            : `Moyenne du semestre ${current}`
                    }}
                </span>
                <IconTile size="sm">
                    <CalendarRangeIcon />
                </IconTile>
            </span>
            <span class="stat__value">
                {{ currentValue !== null ? fmt(currentValue) : '—' }}
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
            <span class="flex items-start justify-between gap-2">
                <span class="stat__label">Notes saisies</span>
                <IconTile size="sm">
                    <FileTextIcon />
                </IconTile>
            </span>
            <span class="stat__value">{{ grades.length }}</span>
            <span class="stat__foot">
                {{
                    current === null
                        ? 'aucune pour l’instant'
                        : `${inCurrent} au semestre ${current}`
                }}
            </span>
        </div>

        <div class="stat">
            <span class="flex items-start justify-between gap-2">
                <span class="stat__label">Notes insuffisantes</span>
                <IconTile
                    size="sm"
                    :class="
                        failed > 0
                            ? 'text-destructive'
                            : 'text-muted-foreground'
                    "
                >
                    <TriangleAlertIcon />
                </IconTile>
            </span>
            <span class="stat__value">{{ failed }}</span>
            <span class="stat__foot"
                >sur {{ grades.length }} notes, seuil 4.0</span
            >
        </div>
    </section>
</template>
