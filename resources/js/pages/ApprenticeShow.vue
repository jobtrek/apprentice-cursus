<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { UserXIcon } from '@lucide/vue';
import ApprenticeMetaRow from '@/components/apprentice/ApprenticeMetaRow.vue';
import GradeBook from '@/components/gradeList/GradeBook.vue';
import { PageContainer, PageHeader } from '@/components/page';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { apprentisdashboard } from '@/routes';
import apprentices from '@/routes/apprentices';
import type { Apprentice } from '@/types/apprentice';
import type { Grade } from '@/types/grade';

const props = defineProps<{
    apprenticeId: number;
    apprentice: Apprentice;
    grades: Grade[];
}>();

const breadcrumbs = [{ label: 'Apprentis', href: apprentisdashboard() }];

const gradeHref = (grade: Grade) =>
    apprentices.grades.show({
        apprentice: props.apprenticeId,
        grade: grade.id,
    });
</script>

<template>
    <Head :title="apprentice?.name ?? 'Apprenti·e introuvable'" />

    <PageContainer size="lg">
        <template v-if="apprentice">
            <PageHeader
                :title="apprentice.name"
                :description="
                    [
                        apprentice.track,
                        apprentice.year && `${apprentice.year} année`,
                    ]
                        .filter(Boolean)
                        .join(' · ')
                "
                :breadcrumbs="breadcrumbs"
            >
                <ApprenticeMetaRow
                    class="mt-2 max-w-sm"
                    :coach="apprentice.coach"
                />
            </PageHeader>

            <GradeBook :grades="grades" :grade-href="gradeHref" />
        </template>

        <template v-else>
            <PageHeader
                title="Apprenti·e introuvable"
                :breadcrumbs="breadcrumbs"
            />

            <Empty class="border">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <UserXIcon />
                    </EmptyMedia>
                    <EmptyTitle>Aucun·e apprenti·e ne correspond</EmptyTitle>
                    <EmptyDescription>
                        Cet·te apprenti·e n'existe pas ou n'est plus suivi·e.
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button as-child variant="outline">
                        <Link :href="apprentisdashboard()">
                            Retour à la liste
                        </Link>
                    </Button>
                </EmptyContent>
            </Empty>
        </template>
    </PageContainer>
</template>
