<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRightIcon } from '@lucide/vue';
import { computed } from 'vue';
import { StatItem } from '@/components/page';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { getInitials } from '@/composables/useInitials';
import { PASSING_GRADE } from '@/data/dashboard';
import { averageStatus } from '@/lib/apprentice';
import apprentices from '@/routes/apprentices';
import type { ApprenticeListItem } from '@/types/apprentice';
import ApprenticeMetaRow from './ApprenticeMetaRow.vue';
import ScoreGauge from './ScoreGauge.vue';

const props = defineProps<{
    apprentice: ApprenticeListItem | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const average = computed(() => props.apprentice?.stats?.average ?? null);

const status = computed(() =>
    average.value === null ? null : averageStatus(average.value),
);
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent class="overflow-y-auto">
            <SheetHeader>
                <div class="flex items-center gap-3">
                    <Avatar class="size-10">
                        <AvatarFallback>
                            {{ getInitials(apprentice?.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="flex flex-col gap-1">
                        <SheetTitle>{{ apprentice?.name }}</SheetTitle>
                        <SheetDescription class="flex items-center gap-1.5">
                            <Badge v-if="apprentice?.track" variant="outline">
                                {{ apprentice.track }}
                            </Badge>
                            <template v-else>Sans filière</template>
                        </SheetDescription>
                    </div>
                </div>
            </SheetHeader>

            <div v-if="apprentice" class="flex flex-col gap-6 px-4 pb-6">
                <Separator />

                <ApprenticeMetaRow
                    :coach="apprentice.coach ?? undefined"
                    :formateur="apprentice.trainer ?? undefined"
                />

                <section
                    class="bg-card flex flex-col items-center gap-1 rounded-xl border px-6 py-6"
                    aria-label="Moyenne des notes"
                >
                    <p class="text-muted-foreground text-sm">
                        Moyenne des notes
                    </p>

                    <ScoreGauge
                        :value="average ?? 0"
                        :size="150"
                        :stroke-width="12"
                        :color="status?.color"
                        class="my-2"
                    >
                        <span class="text-3xl font-semibold tabular-nums">
                            {{ average !== null ? average.toFixed(1) : '—' }}
                        </span>
                    </ScoreGauge>

                    <p
                        class="text-sm font-medium"
                        :class="status?.class ?? 'text-muted-foreground'"
                    >
                        {{ status?.label ?? 'Aucune note pour l’instant' }}
                    </p>
                    <p class="text-muted-foreground text-xs">
                        Seuil de réussite : {{ PASSING_GRADE.toFixed(1) }} sur 6
                    </p>
                </section>

                <div class="grid grid-cols-2 gap-4">
                    <StatItem label="Notes saisies">
                        <span class="tabular-nums">
                            {{ apprentice.stats?.grades_count ?? 0 }}
                        </span>
                    </StatItem>
                    <StatItem label="Dernière note">
                        <span class="tabular-nums">
                            {{ apprentice.stats?.last_grade_date ?? '—' }}
                        </span>
                    </StatItem>
                </div>

                <p class="text-muted-foreground text-xs">
                    Moyenne simple des notes saisies, pas la moyenne pondérée du
                    CFC.
                </p>
            </div>

            <SheetFooter v-if="apprentice?.canView" class="border-t">
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
                        Voir le portfolio
                    </Link>
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
