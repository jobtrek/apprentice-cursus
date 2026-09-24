<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AddActionButton from '@/components/AddActionButton.vue';
import AddGradeDialog from '@/components/grade/AddGradeDialog.vue';
import GradeBook from '@/components/gradeList/GradeBook.vue';
import { PageContainer, PageHeader } from '@/components/page';
import { useAddGradeDialog } from '@/composables/useAddGradeDialog';
import grades from '@/routes/grades';
import type { Grade } from '@/types/grade';

const pageTitle = 'Carnet de notes';

const gradeHref = (grade: Grade) => grades.show(grade.id);

const { open: openAddGrade } = useAddGradeDialog();
</script>

<template>
    <Head :title="pageTitle" />

    <PageContainer size="lg">
        <PageHeader :title="pageTitle">
            <template #actions>
                <AddActionButton
                    label="Ajouter une note"
                    data-test="add-grade-button"
                    @click="openAddGrade"
                />
            </template>
        </PageHeader>

        <GradeBook :grade-href="gradeHref" />

        <AddGradeDialog />
    </PageContainer>
</template>
