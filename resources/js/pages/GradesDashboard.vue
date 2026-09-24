<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AddGradeButton from '@/components/grade/AddGradeButton.vue';
import AddGradeDialog from '@/components/grade/AddGradeDialog.vue';
import GradeBook from '@/components/gradeList/GradeBook.vue';
import { PageContainer, PageHeader } from '@/components/page';
import grades from '@/routes/grades';
import type { Grade } from '@/types/grade';

const pageTitle = 'Carnet de notes';

const gradeHref = (grade: Grade) => grades.show(grade.id);
</script>

<template>
    <Head :title="pageTitle" />

    <PageContainer size="lg">
        <PageHeader :title="pageTitle">
            <template #actions>
                <AddGradeButton />
            </template>
        </PageHeader>

        <GradeBook :grade-href="gradeHref" />

        <AddGradeDialog />
    </PageContainer>
</template>
