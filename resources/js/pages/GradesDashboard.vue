<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { PlusIcon } from '@lucide/vue';
import { computed } from 'vue';
import AddGradeDialog from '@/components/grade/AddGradeDialog.vue';
import GradebookSummary from '@/components/gradebook/GradebookSummary.vue';
import GradeList from '@/components/gradebook/GradeList.vue';
import { PageContainer, PageHeader } from '@/components/page';
import { Button } from '@/components/ui/button';
import { useAddGradeDialog } from '@/composables/useAddGradeDialog';
import { createGradebook, type GradeTree } from '@/lib/gradebook';
import type { Grade } from '@/types/grade';

const props = defineProps<{
    grades: Grade[];
    /** Null si l'apprenti·e n'a pas encore de filière. */
    tree: GradeTree | null;
}>();

const gradebook = computed(() =>
    props.tree ? createGradebook(props.tree, props.grades) : null,
);

const { open: openAddGrade } = useAddGradeDialog();
</script>

<template>
    <Head title="Carnet de notes" />

    <PageContainer size="lg">
        <PageHeader
            title="Carnet de notes"
            description="Vos notes et vos moyennes par domaine, pondérées comme pour le CFC."
        >
            <template #actions>
                <Button data-test="add-grade-button" @click="openAddGrade">
                    <PlusIcon aria-hidden="true" />
                    Ajouter une note
                </Button>
            </template>
        </PageHeader>

        <template v-if="gradebook">
            <GradebookSummary :gradebook="gradebook" />
            <GradeList :gradebook="gradebook" :grades="grades" />
        </template>
        <p v-else class="text-muted-foreground text-sm">
            Aucune filière n'est encore attribuée à votre compte : le carnet
            apparaîtra dès qu'elle le sera.
        </p>

        <AddGradeDialog />
    </PageContainer>
</template>
