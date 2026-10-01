<script setup lang="ts">
import { useRemember } from '@inertiajs/vue3';
import { ChevronDownIcon, SearchIcon } from '@lucide/vue';
import { computed, reactive } from 'vue';
import { fmt, fmtWeight, type Gradebook, plural } from '@/lib/gradebook';
import type { Grade } from '@/types/grade';
import GradeTable from './GradeTable.vue';
import Segmented from './Segmented.vue';

const props = defineProps<{ gradebook: Gradebook; grades: Grade[] }>();

type View = { q: string; semester: number; closed: Record<number, boolean> };

// Recherche, semestre et groupes repliés gardés au retour d'une note.
const view = useRemember(
    reactive<View>({ q: '', semester: 0, closed: {} }),
    'GradeList',
) as View;

const semesterOptions = computed(() => [
    { value: 0, label: 'Tous' },
    ...[...new Set(props.grades.map((grade) => grade.semester))]
        .sort((a, b) => a - b)
        .map((s) => ({ value: s, label: `S${s}` })),
]);

const query = computed(() => view.q.trim().toLowerCase());
const filtering = computed(() => Boolean(query.value || view.semester));

const matches = (grade: Grade): boolean =>
    (!view.semester || grade.semester === view.semester) &&
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
                <label class="gb-search">
                    <SearchIcon aria-hidden="true" />
                    <span class="sr-only">Filtrer les notes</span>
                    <input
                        v-model="view.q"
                        type="search"
                        placeholder="Rechercher une note…"
                        autocomplete="off"
                    />
                </label>
                <Segmented
                    v-model="view.semester"
                    :options="semesterOptions"
                    label="Semestre"
                />
            </div>
        </div>

        <div class="gb-list">
            <div v-if="!shown && filtering" class="gb-empty">
                <p class="gb-empty__title">Aucune note trouvée</p>
                <p>Essayez un autre terme ou un autre semestre.</p>
                <button type="button" class="gb-link" @click="reset">
                    Réinitialiser les filtres
                </button>
            </div>

            <template v-else>
                <template v-for="group in groups" :key="group.id">
                    <details
                        v-if="!filtering || group.visible.length"
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
                                    <strong>{{
                                        fmt(group.average)
                                    }}</strong></template
                                >
                            </span>
                        </summary>
                        <div class="gb-group__body">
                            <p v-if="!group.all.length" class="gb-empty-row">
                                Pas encore de note pour ce domaine.
                            </p>
                            <template v-else-if="group.subs.length">
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
                                        <GradeTable :grades="sub.visible" />
                                    </template>
                                </template>
                                <GradeTable
                                    v-if="group.rest.length"
                                    :grades="group.rest"
                                />
                            </template>
                            <GradeTable v-else :grades="group.visible" />
                        </div>
                    </details>
                </template>
            </template>
        </div>
    </section>
</template>
