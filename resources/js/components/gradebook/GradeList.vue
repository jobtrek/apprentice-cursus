<script setup lang="ts">
import { useRemember } from '@inertiajs/vue3';
import { ChevronDownIcon, CircleDashedIcon } from '@lucide/vue';
import { computed, reactive } from 'vue';
import SearchInput from '@/components/SearchInput.vue';
import { PASSING_GRADE } from '@/data/dashboard';
import { fmt, fmtWeight, type Gradebook, plural } from '@/lib/gradebook';
import type { Grade } from '@/types/grade';
import GradeTable from './GradeTable.vue';
import Segmented from './Segmented.vue';

const props = defineProps<{ gradebook: Gradebook; grades: Grade[] }>();

/** Toutes les notes, celles sous le seuil, ou celles qui ont des commentaires. */
type ResultFilter = 'all' | 'insufficient' | 'commented';

type View = {
    q: string;
    semester: number;
    result: ResultFilter;
    closed: Record<number, boolean>;
};

// Recherche, filtres et groupes repliés gardés au retour d'une note.
const view = useRemember(
    reactive<View>({ q: '', semester: 0, result: 'all', closed: {} }),
    'GradeList',
) as View;
// Un état mémorisé avant l'ajout du filtre de résultat n'a pas ce champ.
view.result ??= 'all';

const RESULT_OPTIONS: { value: ResultFilter; label: string }[] = [
    { value: 'all', label: 'Toutes' },
    { value: 'insufficient', label: 'Insuffisantes' },
    { value: 'commented', label: 'Commentées' },
];

const semesterOptions = computed(() => [
    { value: 0, label: 'Tous' },
    ...[...new Set(props.grades.map((grade) => grade.semester))]
        .sort((a, b) => a - b)
        .map((s) => ({ value: s, label: `S${s}` })),
]);

const query = computed(() => view.q.trim().toLowerCase());
const filtering = computed(() =>
    Boolean(query.value || view.semester || view.result !== 'all'),
);

const matchesResult = (grade: Grade): boolean => {
    switch (view.result) {
        case 'insufficient':
            return grade.value < PASSING_GRADE;
        case 'commented':
            return (grade.comments_count ?? 0) > 0;
        default:
            return true;
    }
};

const matches = (grade: Grade): boolean =>
    (!view.semester || grade.semester === view.semester) &&
    matchesResult(grade) &&
    (!query.value ||
        `${grade.title} ${grade.subject}`.toLowerCase().includes(query.value));

// Domaines sans note (souvent le TPI) en dernier.
const groups = computed(() =>
    props.gradebook.domains
        .map((id) => {
            const all = props.gradebook.gradesUnder(id);
            const subs = (props.gradebook.node(id)?.children ?? [])
                .filter((child) => props.gradebook.node(child.id)?.aggregated)
                .map((child) => {
                    const subAll = props.gradebook.gradesUnder(child.id);

                    return {
                        id: child.id,
                        weight: child.weight,
                        name: props.gradebook.name(child.id),
                        average: props.gradebook.nodeValue(child.id, subAll),
                        visible: subAll.filter(matches),
                    };
                });

            const visible = all.filter(matches);
            const inSubs = new Set(
                subs.flatMap((sub) => sub.visible.map((grade) => grade.id)),
            );

            return {
                id,
                name: props.gradebook.name(id),
                all,
                visible,
                average: props.gradebook.nodeValue(id, all),
                subs,
                // Notes posées directement sur une feuille du domaine, hors sous-groupe.
                rest: visible.filter((grade) => !inSubs.has(grade.id)),
            };
        })
        .sort(
            (a, b) => Number(a.all.length === 0) - Number(b.all.length === 0),
        ),
);

const shown = computed(() =>
    groups.value.reduce((n, group) => n + group.visible.length, 0),
);

function reset(): void {
    view.q = '';
    view.semester = 0;
    view.result = 'all';
}

function onToggle(id: number, event: Event): void {
    view.closed[id] = !(event.target as HTMLDetailsElement).open;
}
</script>

<template>
    <section class="gb-notes" aria-labelledby="gb-notes-title">
        <div class="gb-toolbar">
            <div class="flex min-w-0 items-baseline gap-2">
                <h2
                    id="gb-notes-title"
                    class="text-lg font-semibold tracking-tight"
                >
                    Notes
                </h2>
                <span class="text-muted-foreground text-sm">
                    {{
                        filtering
                            ? `${shown} sur ${grades.length} notes`
                            : plural(grades.length, 'note')
                    }}
                </span>
            </div>
            <div class="gb-toolbar__controls">
                <SearchInput
                    v-model="view.q"
                    placeholder="Rechercher une note"
                    class="w-full sm:w-64"
                />
                <Segmented
                    v-model="view.semester"
                    :options="semesterOptions"
                    label="Semestre"
                />
                <Segmented
                    v-model="view.result"
                    :options="RESULT_OPTIONS"
                    label="Résultat"
                />
            </div>
        </div>

        <div class="gb-list">
            <div v-if="!shown && filtering" class="gb-empty">
                <p class="gb-empty__title">Aucune note trouvée</p>
                <p>
                    Essayez un autre terme, un autre semestre ou un autre
                    résultat.
                </p>
                <button type="button" class="gb-link" @click="reset">
                    Réinitialiser les filtres
                </button>
            </div>

            <template v-else>
                <template v-for="group in groups" :key="group.id">
                    <!-- Domaine sans note : une ligne discrète, rien à déplier. -->
                    <div
                        v-if="!group.all.length && !filtering"
                        class="gb-group gb-group--empty"
                    >
                        <CircleDashedIcon
                            class="gb-chevron"
                            aria-hidden="true"
                        />
                        <span class="gb-group__name">{{ group.name }}</span>
                        <span class="gb-group__avg">Pas encore de note</span>
                    </div>
                    <details
                        v-else-if="group.visible.length"
                        class="gb-group"
                        :open="!view.closed[group.id]"
                        @toggle="onToggle(group.id, $event)"
                    >
                        <summary>
                            <ChevronDownIcon
                                class="gb-chevron"
                                aria-hidden="true"
                            />
                            <span class="gb-group__name">{{ group.name }}</span>
                            <span class="gb-group__meta">
                                {{
                                    group.all.length
                                        ? `${filtering ? `${group.visible.length} / ` : ''}${plural(group.all.length, 'note')}`
                                        : 'Aucune note'
                                }}
                            </span>
                            <span class="gb-group__avg">
                                <span
                                    v-if="group.average === null"
                                    class="gb-muted"
                                    >—</span
                                >
                                <template v-else
                                    >Moyenne
                                    <strong
                                        :class="{
                                            'text-destructive':
                                                group.average < PASSING_GRADE,
                                        }"
                                        >{{ fmt(group.average) }}</strong
                                    ></template
                                >
                            </span>
                        </summary>
                        <div class="gb-group__body">
                            <template v-if="group.subs.length">
                                <template
                                    v-for="sub in group.subs"
                                    :key="sub.id"
                                >
                                    <template v-if="sub.visible.length">
                                        <h4 class="gb-sub">
                                            {{ sub.name }}
                                            <span>
                                                {{
                                                    (sub.average === null
                                                        ? ''
                                                        : `Moyenne ${fmt(sub.average)} · `) +
                                                    `pondération ${fmtWeight(sub.weight)} %`
                                                }}
                                            </span>
                                        </h4>
                                        <GradeTable
                                            :grades="sub.visible"
                                            :parent="sub.name"
                                        />
                                    </template>
                                </template>
                                <GradeTable
                                    v-if="group.rest.length"
                                    :grades="group.rest"
                                    :parent="group.name"
                                />
                            </template>
                            <GradeTable
                                v-else
                                :grades="group.visible"
                                :parent="group.name"
                            />
                        </div>
                    </details>
                </template>
            </template>
        </div>
    </section>
</template>
