<script setup lang="ts">
import { TriangleAlertIcon } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { PASSING_GRADE } from '@/data/dashboard';
import type { DomainGrade } from '@/data/gradebook';
import { GRADE_MAX } from '@/constants/constants';

defineProps<{
    domains: DomainGrade[];
}>();

const percent = (grade: number) => `${(grade / GRADE_MAX) * 100}%`;
</script>

<template>
    <ul class="flex flex-col gap-5">
        <li
            v-for="domain in domains"
            :key="domain.title"
            class="flex flex-col gap-2"
        >
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <span class="truncate text-sm font-medium">
                        {{ domain.title }}
                    </span>
                    <Badge variant="secondary">{{ domain.weight }}</Badge>
                </div>
                <span class="text-sm font-semibold tabular-nums">
                    {{ domain.grade.toFixed(1) }}
                </span>
            </div>

            <!-- Jauge sur l'échelle 0–6, avec un repère au seuil de réussite. -->
            <div
                class="bg-muted relative h-2 rounded-full"
                role="meter"
                :aria-label="domain.title"
                :aria-valuenow="domain.grade"
                aria-valuemin="0"
                :aria-valuemax="GRADE_MAX"
            >
                <div
                    class="h-full rounded-full"
                    :class="
                        domain.grade < PASSING_GRADE
                            ? 'bg-destructive'
                            : 'bg-chart-1'
                    "
                    :style="{ width: percent(domain.grade) }"
                />
                <span
                    class="bg-foreground/40 absolute -top-1 h-4 w-px"
                    :style="{ left: percent(PASSING_GRADE) }"
                    aria-hidden="true"
                />
            </div>

            <p
                v-if="domain.grade < PASSING_GRADE"
                class="text-destructive flex items-center gap-1 text-xs font-medium"
            >
                <TriangleAlertIcon class="size-3.5" aria-hidden="true" />
                Sous le seuil de {{ PASSING_GRADE.toFixed(1) }}
            </p>
        </li>
    </ul>
</template>
