<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ApprenticeMetaRow from '@/components/apprentice/ApprenticeMetaRow.vue';
import GradeBook from '@/components/gradeList/GradeBook.vue';
import { PageContainer, PageHeader } from '@/components/page';
import { Badge } from '@/components/ui/badge';
import { apprentisdashboard } from '@/routes';
import apprentices from '@/routes/apprentices';
import type { Apprentice } from '@/types/apprentice';
import type { Grade } from '@/types/grade';

const props = defineProps<{
    apprentice: Apprentice;
    grades: Grade[];
}>();

const breadcrumbs = [{ label: 'Apprentis', href: apprentisdashboard() }];

const gradeHref = (grade: Grade) =>
    apprentices.grades.show({
        apprentice: props.apprentice.id,
        grade: grade.id,
    });
</script>

<template>
    <Head :title="apprentice.name" />

    <PageContainer size="lg">
        <PageHeader :title="apprentice.name" :breadcrumbs="breadcrumbs">
            <template #description>
                <span class="flex items-center gap-2">
                    {{ apprentice.apprenticeship ?? '—' }}
                    <Badge v-if="!apprentice.isActive" variant="secondary">
                        Inactif
                    </Badge>
                </span>
            </template>

            <ApprenticeMetaRow
                class="mt-2 max-w-sm"
                :coach="apprentice.coach"
            />
        </PageHeader>

        <GradeBook :grades="grades" :grade-href="gradeHref" />
    </PageContainer>
</template>
