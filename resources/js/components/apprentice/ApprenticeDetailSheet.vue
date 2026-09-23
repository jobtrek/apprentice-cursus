<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { getInitials } from '@/composables/useInitials';
import type { Apprentice } from '@/composables/useApprentices';
import {
    AVERAGE_SCORE,
    BRANCH_SCORES,
    MAX_SCORE,
} from '@/data_2/apprenticesScores';
import ApprenticeMetaRow from './ApprenticeMetaRow.vue';
import ApprenticeScoreSummary from './ApprenticeScoreSummary.vue';
import ApprenticeScoreTable from './ApprenticeScoreTable.vue';

defineProps<{
    apprentice: Apprentice | null;
}>();

const open = defineModel<boolean>('open', { required: true });
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent class="overflow-y-auto">
            <SheetHeader>
                <div class="flex items-center gap-3">
                    <Avatar class="size-10">
                        <AvatarImage :src="apprentice?.avatarUrl ?? ''" />
                        <AvatarFallback>
                            {{ getInitials(apprentice?.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div>
                        <SheetTitle>{{ apprentice?.name }}</SheetTitle>
                        <SheetDescription>
                            {{ apprentice?.track }} · {{ apprentice?.year }}
                        </SheetDescription>
                    </div>
                </div>
            </SheetHeader>

            <div v-if="apprentice" class="flex flex-col gap-6 px-4 pb-6">
                <Separator />

                <ApprenticeMetaRow
                    :coach="apprentice.coach"
                    :formateur="apprentice.trainer"
                />

                <ApprenticeScoreSummary
                    :average="AVERAGE_SCORE"
                    :max="MAX_SCORE"
                />

                <ApprenticeScoreTable :branches="BRANCH_SCORES" />
            </div>
        </SheetContent>
    </Sheet>
</template>
