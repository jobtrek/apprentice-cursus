<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@lucide/vue';
import { computed } from 'vue';
import { byDateDesc, fmt, PASS } from '@/lib/gradebook';
import gradeRoutes from '@/routes/grades';
import type { Grade } from '@/types/grade';
import CommentCount from './CommentCount.vue';

const props = defineProps<{ grades: Grade[] }>();

const sorted = computed(() => [...props.grades].sort(byDateDesc));

const open = (grade: Grade) => router.visit(gradeRoutes.show(grade.id));
</script>

<template>
    <div class="gb-table-wrap">
        <table class="gb-table">
            <thead>
                <tr>
                    <th>Évaluation</th>
                    <th class="gb-col-cat">Catégorie</th>
                    <th class="gb-col-sem">Semestre</th>
                    <th class="gb-col-date">Date</th>
                    <th class="gb-col-grade">Note</th>
                    <th class="gb-col-go">
                        <span class="sr-only">Ouvrir</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="grade in sorted"
                    :key="grade.id"
                    tabindex="0"
                    @click="open(grade)"
                    @keydown.enter.prevent="open(grade)"
                    @keydown.space.prevent="open(grade)"
                >
                    <td>
                        <span class="gb-subject">{{ grade.title }}</span>
                        <CommentCount
                            :count="grade.comments_count"
                            class="gb-comments"
                        />
                    </td>
                    <td class="gb-col-cat">{{ grade.subject }}</td>
                    <td class="gb-col-sem">S{{ grade.semester }}</td>
                    <td class="gb-col-date">{{ grade.date }}</td>
                    <td class="gb-col-grade">
                        <span
                            :class="
                                grade.value < PASS
                                    ? 'gb-grade is-fail'
                                    : 'gb-grade'
                            "
                        >
                            {{ fmt(grade.value) }}
                        </span>
                    </td>
                    <td class="gb-col-go" aria-hidden="true">
                        <ChevronRightIcon />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
