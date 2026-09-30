<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import GradeBook from '@/components/gradeList/GradeBook.vue';
import { PageContainer, PageHeader } from '@/components/page';
import gradeRoutes from '@/routes/grades';
import type { Grade } from '@/types/grade';

defineProps<{
    grades: Grade[];
}>();

const pageTitle = 'Carnet de notes';

const gradeHref = (grade: Grade) => gradeRoutes.show(grade.id);
</script>

<template>
    <Head :title="pageTitle" />

    <PageContainer size="lg">
        <PageHeader :title="pageTitle" />

        <GradeBook :grades="grades" :grade-href="gradeHref" />
    </PageContainer>
</template>
