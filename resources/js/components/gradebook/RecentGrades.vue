<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { byDateDesc, fmt, PASS } from '@/lib/gradebook';
import gradeRoutes from '@/routes/grades';
import type { Grade } from '@/types/grade';
import CommentCount from './CommentCount.vue';

const props = defineProps<{ grades: Grade[] }>();

const recent = computed(() => [...props.grades].sort(byDateDesc).slice(0, 5));
</script>

<template>
    <ul v-if="recent.length" class="recent">
        <li v-for="grade in recent" :key="grade.id">
            <span
                :class="
                    grade.value < PASS
                        ? 'recent__grade is-fail'
                        : 'recent__grade'
                "
            >
                {{ fmt(grade.value) }}
            </span>
            <Link
                :href="gradeRoutes.show(grade.id)"
                class="recent__main hover:underline"
            >
                <span class="recent__title">{{ grade.title }}</span>
                <span class="recent__meta">
                    {{ grade.subject }} · S{{ grade.semester }} ·
                    {{ grade.date }}
                </span>
            </Link>
            <CommentCount
                :count="grade.comments_count"
                class="recent__comments"
            />
        </li>
    </ul>
    <p v-else class="text-muted-foreground text-sm">
        Aucune note pour l'instant.
    </p>
</template>
