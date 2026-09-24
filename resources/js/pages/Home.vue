<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AddActionButton from '@/components/AddActionButton.vue';
import ApprenticeDashboard from '@/components/dashboard/ApprenticeDashboard.vue';
import SupervisorDashboard from '@/components/dashboard/SupervisorDashboard.vue';
import AddGradeDialog from '@/components/grade/AddGradeDialog.vue';
import { PageContainer, PageHeader } from '@/components/page';
import { useAddGradeDialog } from '@/composables/useAddGradeDialog';
import { useNavigation } from '@/composables/useNavigation';

const page = usePage();
const { role } = useNavigation();
const { open: openAddGrade } = useAddGradeDialog();

const firstName = computed(
    () => page.props.auth?.user?.name?.split(/\s+/)[0] ?? '',
);

/** Coachs et formateurs suivent des apprentis ; les autres rôles voient leur propre parcours. */
const isApprentice = computed(() => role.value === 'apprentice');
</script>

<template>
    <Head title="Accueil" />

    <PageContainer size="lg">
        <PageHeader
            :title="firstName ? `Bonjour ${firstName}` : 'Accueil'"
            :description="
                isApprentice
                    ? 'Voici où vous en êtes dans votre formation.'
                    : 'Voici où en sont les apprentis que vous suivez.'
            "
        >
            <template v-if="isApprentice" #actions>
                <AddActionButton
                    label="Ajouter une note"
                    @click="openAddGrade"
                />
            </template>
        </PageHeader>

        <template v-if="isApprentice">
            <ApprenticeDashboard />
            <AddGradeDialog />
        </template>
        <SupervisorDashboard v-else />
    </PageContainer>
</template>
