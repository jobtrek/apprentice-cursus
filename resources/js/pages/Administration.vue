<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import ApprenticeSearchBar from '@/components/apprentice/ApprenticeSearchBar.vue';
import TabFilter from '@/components/TabFilter.vue';
import DataTable from '@/components/DataTable.vue';
import SubjectTableRow from '@/components/subject/SubjectTableRow.vue';
import NewSubjectDialog, {
    type NewSubjectPayload,
} from '@/components/subject/NewSubjectDialog.vue';
import rawSubjects from '@/data_2/subjects.json';
import { PageContainer, PageHeader } from '@/components/page';

type Subject = {
    id: number;
    name: string;
    domain: string;
    track: string;
    status: 'Active' | 'Désactivée';
    hasGrades: boolean;
};

const subjectColumns = [
    {
        key: 'name',
        label: 'MATIÈRE',
        class: 'w-[40%] uppercase text-xs font-semibold',
    },
    {
        key: 'domain',
        label: 'DOMAINE / MODULE',
        class: 'uppercase text-xs font-semibold',
    },
    {
        key: 'track',
        label: 'FILIÈRE',
        class: 'uppercase text-xs font-semibold',
    },
    { key: 'actions', label: '', class: 'text-right' },
];

const trackOptions = [
    { label: 'All', value: 'All' },
    { label: 'IT', value: 'IT' },
    { label: 'EC', value: 'EC' },
] as const;

const trackMap: Record<string, string> = {
    IT: 'Informatique',
    EC: 'Employé-e de commerce',
};

const currentTab = ref('matieres');
const searchQuery = ref('');
const selectedTrack = ref<'All' | 'IT' | 'EC'>('All');
const createdSubjects = ref<Subject[]>([]);
const overriddenSubjects = ref<Record<number, Subject>>({});
const deletedSubjectIds = ref<number[]>([]);

const allSubjects = computed<Subject[]>(() => [
    ...(rawSubjects as Subject[])
        .filter((subject) => !deletedSubjectIds.value.includes(subject.id))
        .map((subject) => overriddenSubjects.value[subject.id] ?? subject),
    ...createdSubjects.value,
]);

function handleCreateSubject(payload: NewSubjectPayload) {
    const existingIds = allSubjects.value.map((subject) => subject.id);
    const nextId = existingIds.length ? Math.max(...existingIds) + 1 : 1;

    createdSubjects.value.push({
        id: nextId,
        name: payload.name,
        domain: payload.domain,
        track: trackMap[payload.track],
        status: 'Active',
        hasGrades: false,
    });
}

function applyUpdate(id: number, changes: Partial<Subject>) {
    const created = createdSubjects.value.find((subject) => subject.id === id);
    if (created) {
        Object.assign(created, changes);
        return;
    }

    const original = (rawSubjects as Subject[]).find(
        (subject) => subject.id === id,
    );
    if (original) {
        overriddenSubjects.value[id] = { ...original, ...changes };
    }
}

function handleSaveSubject(payload: {
    id: number;
    name: string;
    domain: string;
    track: 'IT' | 'EC';
}) {
    applyUpdate(payload.id, {
        name: payload.name,
        domain: payload.domain,
        track: trackMap[payload.track],
    });
}

function handleDeactivateSubject(id: number) {
    applyUpdate(id, { status: 'Désactivée' });
}

function handleReactivateSubject(id: number) {
    applyUpdate(id, { status: 'Active' });
}

function handleDeleteSubject(id: number) {
    const createdIndex = createdSubjects.value.findIndex(
        (subject) => subject.id === id,
    );
    if (createdIndex !== -1) {
        createdSubjects.value.splice(createdIndex, 1);
        return;
    }

    deletedSubjectIds.value.push(id);
}

const filteredSubjects = computed(() => {
    const searchLower = searchQuery.value.toLowerCase();

    return allSubjects.value.filter((subject) => {
        const matchesSearch = subject.name.toLowerCase().includes(searchLower);
        const matchesTrack =
            selectedTrack.value === 'All' ||
            subject.track === trackMap[selectedTrack.value];

        return matchesSearch && matchesTrack;
    });
});
</script>

<template>
    <Head title="Administration" />

    <PageContainer>
        <PageHeader title="Administration" />

        <Tabs v-model="currentTab" class="w-full">
            <TabsList
                class="bg-muted grid h-12 w-full grid-cols-2 rounded-xl border"
            >
                <TabsTrigger value="comptes" class="rounded-lg text-base">
                    Comptes
                </TabsTrigger>
                <TabsTrigger value="matieres" class="rounded-lg text-base">
                    Matières
                </TabsTrigger>
            </TabsList>

            <TabsContent value="matieres" class="flex flex-col gap-4">
                <ApprenticeSearchBar v-model="searchQuery" />

                <div
                    class="flex flex-col items-start justify-between gap-4 sm:flex-row"
                >
                    <div class="w-full sm:max-w-2xl">
                        <TabFilter
                            v-model="selectedTrack"
                            :options="trackOptions"
                        />
                    </div>

                    <NewSubjectDialog @create="handleCreateSubject" />
                </div>

                <DataTable
                    :columns="subjectColumns"
                    :data="filteredSubjects"
                    empty-message="Aucune matière trouvée."
                >
                    <template #row="{ item }">
                        <SubjectTableRow
                            :subject="item"
                            @save="handleSaveSubject"
                            @deactivate="handleDeactivateSubject"
                            @reactivate="handleReactivateSubject"
                            @delete="handleDeleteSubject"
                        />
                    </template>
                </DataTable>

                <p class="text-muted-foreground text-xs">
                    Une matière déjà utilisée dans au moins une note ne peut pas
                    être supprimée — elle peut uniquement être désactivée. Elle
                    reste visible dans les carnets existants.
                </p>
            </TabsContent>

            <TabsContent value="comptes">
                <div class="text-muted-foreground py-8 text-center">
                    Contenu des comptes (en cours de développement)
                </div>
            </TabsContent>
        </Tabs>
    </PageContainer>
</template>
