<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from "@/components/ui/sheet";
import ApprenticeMetaRow from "./ApprenticeMetaRow.vue";
import ApprenticeScoreSummary from "./ApprenticeScoreSummary.vue";
import ApprenticeScoreTable from "./ApprenticeScoreTable.vue";
import {
    AVERAGE_SCORE,
    BRANCH_SCORES,
    MAX_SCORE,
} from "@/data_2/apprenticesScores";
import type { Apprentice } from "@/composables/useApprentices";

defineProps<{
    apprentice: Apprentice | null;
}>();

const open = defineModel<boolean>("open", { required: true });
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent class="overflow-y-auto">
            <SheetHeader>
                <div class="flex items-center gap-3">
                    <Avatar>
                        <AvatarImage :src="apprentice?.avatarUrl ?? ''" />
                        <AvatarFallback>{{
                            apprentice?.name.charAt(0)
                        }}</AvatarFallback>
                    </Avatar>
                    <div>
                        <SheetTitle>{{ apprentice?.name }}</SheetTitle>
                        <p class="text-sm text-muted-foreground">
                            {{ apprentice?.track }} · {{ apprentice?.year }}
                        </p>
                    </div>
                </div>
            </SheetHeader>

            <div v-if="apprentice" class="mt-2">
                <div class="border-t pt-4 ml-4">
                    <ApprenticeMetaRow
                        :coach="apprentice.coach"
                        :formateur="apprentice.trainer"
                    />
                </div>

                <div class="border-t mt-4 pt-6">
                    <ApprenticeScoreSummary
                        :average="AVERAGE_SCORE"
                        :max="MAX_SCORE"
                    />
                </div>

                <div class="border-t mt-6 pt-4 px-4">
                    <ApprenticeScoreTable :branches="BRANCH_SCORES" />
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
