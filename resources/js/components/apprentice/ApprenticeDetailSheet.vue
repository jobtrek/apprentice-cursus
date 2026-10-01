<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRightIcon } from '@lucide/vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
import {
    AVERAGE_SCORE,
    BRANCH_SCORES,
    MAX_SCORE,
} from '@/data/apprenticesScores';
import ApprenticeMetaRow from './ApprenticeMetaRow.vue';
import ApprenticeScoreSummary from './ApprenticeScoreSummary.vue';
import ApprenticeScoreTable from './ApprenticeScoreTable.vue';
import apprentices from '@/routes/apprentices';
import type { ApprenticeSummary } from '@/types/apprentice';

defineProps<{
    apprentice: ApprenticeSummary | null;
}>();

const open = defineModel<boolean>('open', { required: true });
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
                    <div>
                        <SheetTitle>{{ apprentice?.name }}</SheetTitle>
                        <SheetDescription>
                            {{ apprentice?.track ?? 'Sans filière' }}
                        </SheetDescription>
                    </div>
                </div>
            </SheetHeader>

            <div v-if="apprentice" class="flex flex-col gap-6 px-4 pb-6">
                <Separator />

                <ApprenticeMetaRow
                    :coach="apprentice.coach?.name"
                    :formateur="apprentice.trainer?.name"
                />

                <ApprenticeScoreSummary
                    :average="AVERAGE_SCORE"
                    :max="MAX_SCORE"
                />

                <ApprenticeScoreTable :branches="BRANCH_SCORES" />
            </div>

            <SheetFooter v-if="apprentice" class="border-t">
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
