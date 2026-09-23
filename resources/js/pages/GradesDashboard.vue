<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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
        <PageHeader :title="pageTitle" />

        <GradeBook :grade-href="gradeHref" />
    </PageContainer>
</template>
