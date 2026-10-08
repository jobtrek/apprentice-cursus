<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@lucide/vue';
import { averageStatus, TONES } from '@/lib/apprentice';
import type { Grade } from '@/types/grade';
import type { RouteDefinition } from '@/wayfinder';

defineProps<{
    grades: Grade[];
    gradeHref: (grade: Grade) => RouteDefinition<'get'>;
    /** Affiché quand la liste est vide. */
    empty: string;
}>();
</script>

<template>
    <ul v-if="grades.length" class="-my-1 flex flex-col gap-1">
        <li v-for="grade in grades" :key="grade.id">
            <Link
                :href="gradeHref(grade)"
                class="group/grade hover:bg-muted/60 focus-visible:ring-ring/50 -mx-2 flex items-center gap-3 rounded-lg px-2 py-2 transition-colors focus-visible:ring-[3px] focus-visible:outline-none"
            >
                <!-- Note dans une pastille teintée selon son appréciation. -->
                <span
                    class="flex h-10 w-12 shrink-0 items-center justify-center rounded-lg text-base font-semibold tabular-nums"
                    :class="TONES[averageStatus(grade.value).tone].soft"
                >
                    {{ grade.value.toFixed(1) }}
                    <span class="sr-only">
                        ({{ averageStatus(grade.value).label }})
                    </span>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">
                        {{ grade.title }}
                    </p>
                    <p class="text-muted-foreground truncate text-xs">
                        {{ grade.subject }} · Semestre {{ grade.semester }} ·
                        {{ grade.date }}
                    </p>
                </div>
                <ChevronRightIcon
                    class="text-muted-foreground size-4 transition-transform group-hover/grade:translate-x-0.5"
                    aria-hidden="true"
                />
            </Link>
        </li>
    </ul>
    <p v-else class="text-muted-foreground py-6 text-center text-sm">
        {{ empty }}
    </p>
</template>
