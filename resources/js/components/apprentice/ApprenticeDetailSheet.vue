<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRightIcon,
    CalendarClockIcon,
    FileTextIcon,
    FolderOpenIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import { StatItem } from '@/components/page';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { PASSING_GRADE } from '@/data/dashboard';
import {
    averageStatus,
    relativeDate,
    situationOf,
    yearLabel,
} from '@/lib/apprentice';
import apprentices from '@/routes/apprentices';
import type { ApprenticeListItem } from '@/types/apprentice';
import ApprenticeAvatar from './ApprenticeAvatar.vue';
import AssignmentBadge from './AssignmentBadge.vue';
import ScoreGauge from './ScoreGauge.vue';
import StatusBadge from './StatusBadge.vue';
import TrackBadges from './TrackBadges.vue';

const props = defineProps<{
    apprentice: ApprenticeListItem | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const average = computed(() => props.apprentice?.stats?.average ?? null);

const status = computed(() =>
    average.value === null ? null : averageStatus(average.value),
);

const situation = computed(() =>
    props.apprentice ? situationOf(props.apprentice) : null,
);
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent class="gap-0 overflow-y-auto">
            <!-- Bandeau décoratif, l'avatar le chevauche. -->
            <div
                class="from-primary/25 via-primary/10 to-chart-2/25 h-24 shrink-0 bg-linear-to-r"
                aria-hidden="true"
            />

            <SheetHeader class="-mt-10 gap-3 pb-2">
                <ApprenticeAvatar
                    :name="apprentice?.name"
                    class="ring-background size-16 ring-4"
                    fallback-class="text-lg"
                />
                <div class="flex flex-col gap-1.5">
                    <SheetTitle class="text-xl">
                        {{ apprentice?.name }}
                    </SheetTitle>
                    <SheetDescription
                        as="div"
                        class="flex flex-wrap items-center gap-1.5"
                    >
                        <TrackBadges
                            :track="apprentice?.track ?? null"
                            :is-mp="apprentice?.isMp ?? false"
                        />
                        <span v-if="apprentice?.year">
                            · {{ yearLabel(apprentice.year) }}
                        </span>
                        <Badge
                            v-if="apprentice && !apprentice.isActive"
                            variant="outline"
                        >
                            Inactif
                        </Badge>
                    </SheetDescription>
                </div>
                <StatusBadge
                    v-if="situation"
                    :tone="situation.tone"
                    :label="situation.label"
                />
            </SheetHeader>

            <div v-if="apprentice" class="flex flex-col gap-5 px-4 pt-2 pb-6">
                <section
                    class="bg-muted/30 flex flex-col items-center gap-1 rounded-xl border px-6 py-5"
                    aria-label="Moyenne des notes"
                >
                    <p class="text-muted-foreground text-sm font-medium">
                        Moyenne des notes
                    </p>

                    <ScoreGauge
                        :value="average ?? 0"
                        :size="150"
                        :stroke-width="12"
                        :color="status?.color"
                        class="my-2"
                    >
                        <span class="flex flex-col items-center">
                            <span class="text-3xl font-semibold tabular-nums">
                                {{
                                    average !== null ? average.toFixed(1) : '—'
                                }}
                            </span>
                            <span class="text-muted-foreground text-xs">
                                sur 6
                            </span>
                        </span>
                    </ScoreGauge>

                    <p
                        class="text-sm font-medium"
                        :class="status?.class ?? 'text-muted-foreground'"
                    >
                        {{ status?.label ?? 'Aucune note pour l’instant' }}
                    </p>
                    <p class="text-muted-foreground text-xs">
                        Seuil de réussite : {{ PASSING_GRADE.toFixed(1) }}
                    </p>
                </section>

                <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-2 rounded-xl border p-3">
                        <span
                            class="bg-primary/10 text-primary flex size-8 items-center justify-center rounded-lg"
                        >
                            <FileTextIcon class="size-4" aria-hidden="true" />
                        </span>
                        <span class="text-xl font-semibold tabular-nums">
                            {{ apprentice.stats?.grades_count ?? 0 }}
                        </span>
                        <span class="text-muted-foreground text-xs">
                            Notes saisies
                        </span>
                    </div>
                    <div class="flex flex-col gap-2 rounded-xl border p-3">
                        <span
                            class="bg-primary/10 text-primary flex size-8 items-center justify-center rounded-lg"
                        >
                            <CalendarClockIcon
                                class="size-4"
                                aria-hidden="true"
                            />
                        </span>
                        <span
                            class="text-xl font-semibold first-letter:uppercase"
                            :title="
                                apprentice.stats?.last_grade_date ?? undefined
                            "
                        >
                            {{
                                apprentice.stats?.last_grade_date
                                    ? relativeDate(
                                          apprentice.stats.last_grade_date,
                                      )
                                    : '—'
                            }}
                        </span>
                        <span class="text-muted-foreground text-xs">
                            Dernière note
                        </span>
                    </div>
                </div>

                <div class="flex flex-col gap-4 rounded-xl border p-4">
                    <StatItem label="Coach">
                        <AssignmentBadge
                            :value="apprentice.coach ?? undefined"
                        />
                    </StatItem>
                    <StatItem label="Formateur">
                        <AssignmentBadge
                            :value="apprentice.trainer ?? undefined"
                        />
                    </StatItem>
                </div>

                <p class="text-muted-foreground text-xs">
                    Moyenne simple des notes saisies, pas la moyenne pondérée du
                    CFC.
                </p>
            </div>

            <SheetFooter
                v-if="apprentice?.canView"
                class="bg-background sticky bottom-0 mt-auto border-t"
            >
                <Button as-child>
                    <Link
                        :href="
                            apprentices.show(apprentice.id, {
                                query: { tab: 'grades' },
                            })
                        "
                    >
                        Voir le carnet de notes
                        <ArrowRightIcon aria-hidden="true" />
                    </Link>
                </Button>
                <Button as-child variant="outline">
                    <Link
                        :href="
                            apprentices.show(apprentice.id, {
                                query: { tab: 'portfolio' },
                            })
                        "
                    >
                        <FolderOpenIcon aria-hidden="true" />
                        Voir le portfolio
                    </Link>
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
