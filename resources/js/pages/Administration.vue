<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { UsersIcon } from '@lucide/vue';
import DataTable from '@/components/DataTable.vue';
import { PageContainer, PageHeader } from '@/components/page';
import SearchInput from '@/components/SearchInput.vue';
import NewSubjectDialog, {
    type NewSubjectPayload,
} from '@/components/subject/NewSubjectDialog.vue';
import SubjectTableRow from '@/components/subject/SubjectTableRow.vue';
import TabFilter from '@/components/TabFilter.vue';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { TRACK_FILTER_OPTIONS } from '@/constants/constants';
import rawSubjects from '@/data_2/subjects.json';

type Subject = {
    id: number;
    name: string;
    domain: string;
    track: string;
    status: 'Active' | 'Désactivée';
    hasGrades: boolean;
};

const subjectColumns = [
    { key: 'name', label: 'Matière', class: 'w-1/2' },
    { key: 'domain', label: 'Domaine / Module', class: 'w-1/4' },
    { key: 'track', label: 'Filière', class: 'w-20' },
    { key: 'actions', label: '', class: 'w-0' },
];

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

    <PageContainer size="lg">
        <PageHeader
            title="Administration"
            description="Gérez les comptes et le référentiel des matières."
        />

        <Tabs v-model="currentTab" class="gap-6">
            <TabsList>
                <TabsTrigger value="matieres" class="px-3">
                    Matières
                </TabsTrigger>
                <TabsTrigger value="comptes" class="px-3">Comptes</TabsTrigger>
            </TabsList>

            <TabsContent value="matieres" class="flex flex-col gap-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <SearchInput
                        v-model="searchQuery"
                        placeholder="Rechercher une matière"
                        class="lg:max-w-xs"
                    />
                    <TabFilter
                        v-model="selectedTrack"
                        :options="TRACK_FILTER_OPTIONS"
                        label="Filtrer par filière"
                    />
                    <div class="lg:ml-auto">
                        <NewSubjectDialog @create="handleCreateSubject" />
                    </div>
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

                <p class="text-muted-foreground text-sm">
                    Une matière déjà utilisée dans au moins une note ne peut pas
                    être supprimée — elle peut uniquement être désactivée. Elle
                    reste visible dans les carnets existants.
                </p>
            </TabsContent>

            <TabsContent value="comptes">
                <Empty class="border">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <UsersIcon />
                        </EmptyMedia>
                        <EmptyTitle>Gestion des comptes</EmptyTitle>
                        <EmptyDescription>
                            Cette section est en cours de développement.
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>
            </TabsContent>
        </Tabs>
    </PageContainer>
</template>
