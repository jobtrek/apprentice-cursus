<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ApprenticeDashboard from '@/components/dashboard/ApprenticeDashboard.vue';
import CoachDashboard from '@/components/dashboard/CoachDashboard.vue';

const page = usePage();

/**
 * L'accueil dépend du rôle : `role` vient de la colonne `users.role` partagée
 * par `HandleInertiaRequests`. Les rôles d'administration n'ont pas encore de
 * tableau de bord dédié.
 */
const role = computed(() =>
    String(page.props.auth?.user?.role ?? 'apprentice'),
);

const isApprentice = computed(() => role.value === 'apprentice');
const isSupervisor = computed(() => ['coach', 'trainer'].includes(role.value));

const roleLabel = computed(() =>
    role.value === 'trainer'
        ? 'le formateur ou la formatrice'
        : 'le ou la coach',
);
</script>

<template>
    <Head title="Accueil" />

    <ApprenticeDashboard v-if="isApprentice" />

    <CoachDashboard v-else-if="isSupervisor" :role-label="roleLabel" />

    <div v-else class="w-full max-w-5xl">
        <h1 class="text-2xl font-semibold">Accueil</h1>
        <p class="text-muted-foreground mt-2 text-sm">
            Aucun tableau de bord n'est encore prévu pour le rôle «
            {{ role }} ».
        </p>
    </div>
</template>
