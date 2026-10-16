<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon, MessageSquareIcon } from '@lucide/vue';
import ApprenticeAvatar from '@/components/apprentice/ApprenticeAvatar.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { averageStatus, TONES } from '@/lib/apprentice';
import { formatDate } from '@/lib/date';
import apprentices from '@/routes/apprentices';
import type { SupervisedGrade } from '@/types/apprentice';

/**
 * Dernières notes des apprentis suivis : ce qui est nouveau depuis la dernière
 * visite, et les notes qui attendent encore un retour.
 */
defineProps<{ grades: SupervisedGrade[] }>();
</script>

<template>
    <Card class="shadow-xs">
        <CardHeader>
            <CardTitle>Dernières notes</CardTitle>
            <CardDescription>
                Les plus récentes de vos apprentis, par date de test
            </CardDescription>
        </CardHeader>
        <CardContent>
            <ul v-if="grades.length" class="-my-1 divide-y">
                <li v-for="grade in grades" :key="grade.id">
                    <Link
                        :href="
                            apprentices.grades.show({
                                apprentice: grade.apprentice_id,
                                grade: grade.id,
                            })
                        "
                        class="group/grade hover:bg-muted/50 focus-visible:ring-ring -mx-2 flex items-center gap-3 rounded-md px-2 py-2.5 transition-colors focus-visible:ring-[3px] focus-visible:outline-none"
                    >
                        <ApprenticeAvatar
                            :name="grade.apprentice"
                            class="size-8"
                            fallback-class="text-xs"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ grade.apprentice }}
                            </p>
                            <p class="text-muted-foreground truncate text-xs">
                                {{ grade.title }} · {{ formatDate(grade.date) }}
                            </p>
                        </div>
                        <Badge
                            v-if="grade.comments_count === 0"
                            variant="outline"
                            class="text-muted-foreground hidden border-dashed font-normal sm:inline-flex"
                        >
                            <MessageSquareIcon aria-hidden="true" />
                            À commenter
                        </Badge>
                        <span
                            class="flex h-8 w-11 shrink-0 items-center justify-center rounded-md text-sm font-semibold tabular-nums"
                            :class="TONES[averageStatus(grade.value).tone].soft"
                        >
                            {{ grade.value.toFixed(1) }}
                            <span class="sr-only">
                                ({{ averageStatus(grade.value).label }})
                            </span>
                        </span>
                        <ChevronRightIcon
                            class="text-muted-foreground size-4 transition-transform group-hover/grade:translate-x-0.5"
                            aria-hidden="true"
                        />
                    </Link>
                </li>
            </ul>
            <p v-else class="text-muted-foreground text-sm">
                Aucune note saisie pour l'instant.
            </p>
        </CardContent>
    </Card>
</template>
