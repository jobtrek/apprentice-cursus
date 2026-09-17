<script setup lang="ts">
import { ref, computed } from "vue";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import ApprenticeSearchBar from "@/components/apprentice/ApprenticeSearchBar.vue";
import TabFilter from "@/components/TabFilter.vue";
import DataTable from "@/components/DataTable.vue";
import SubjectTableRow from "@/components/subject/SubjectTableRow.vue";
import NewSubjectDialog, {
    type NewSubjectPayload,
} from "@/components/subject/NewSubjectDialog.vue";
import rawSubjects from "@/data_2/subjects.json";

type Subject = {
    id: number;
    name: string;
    domain: string;
    track: string;
    status: "Active" | "Désactivée";
    hasGrades: boolean;
};

const subjectColumns = [
    {
        key: "name",
        label: "MATIÈRE",
        class: "w-[40%] uppercase text-xs font-semibold",
    },
    {
        key: "domain",
        label: "DOMAINE / MODULE",
        class: "uppercase text-xs font-semibold",
    },
    {
        key: "track",
        label: "FILIÈRE",
        class: "uppercase text-xs font-semibold",
    },
    { key: "actions", label: "", class: "text-right" },
];

const trackOptions = [
    { label: "All", value: "All" },
    { label: "IT", value: "IT" },
    { label: "EC", value: "EC" },
] as const;

const trackMap: Record<string, string> = {
    IT: "Informatique",
    EC: "Employé-e de commerce",
};

const currentTab = ref("matieres");
const searchQuery = ref("");
const selectedTrack = ref<"All" | "IT" | "EC">("All");
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
        status: "Active",
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
    track: "IT" | "EC";
}) {
    applyUpdate(payload.id, {
        name: payload.name,
        domain: payload.domain,
        track: trackMap[payload.track],
    });
}

function handleDeactivateSubject(id: number) {
    applyUpdate(id, { status: "Désactivée" });
}

function handleReactivateSubject(id: number) {
    applyUpdate(id, { status: "Active" });
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
            selectedTrack.value === "All" ||
            subject.track === trackMap[selectedTrack.value];

        return matchesSearch && matchesTrack;
    });
});
</script>

<template>
    <div class="w-full max-w-4xl font-sans">
        <h1 class="text-2xl font-bold mb-6">Administration</h1>

        <Tabs v-model="currentTab" class="w-full">
            <TabsList
                class="flex justify-start bg-transparent p-0 h-auto border-b rounded-none mb-6"
            >
                <TabsTrigger
                    value="comptes"
                    class="grid h-12 w-full rounded-xl border ""
                >
                    Comptes
                </TabsTrigger>
                <TabsTrigger
                    value="matieres"
                    class="grid h-12 w-full rounded-xl border ""
                >
                    Matières
                </TabsTrigger>
            </TabsList>

            <TabsContent value="matieres">
                <div class="mb-2">
                    <ApprenticeSearchBar v-model="searchQuery" />

                    <div
                        class="flex flex-col sm:flex-row justify-between items-start gap-4"
                    >
                        <div class="w-full sm:max-w-2xl">
                            <TabFilter
                                v-model="selectedTrack"
                                :options="trackOptions"
                            />
                        </div>

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

                <p class="text-xs text-muted-foreground mt-4">
                    Une matière déjà utilisée dans au moins une note ne peut pas
                    être supprimée — elle peut uniquement être désactivée. Elle
                    reste visible dans les carnets existants.
                </p>
            </TabsContent>

            <TabsContent value="comptes">
                <div class="py-8 text-center text-muted-foreground">
                    Contenu des comptes (en cours de développement)
                </div>
            </TabsContent>
        </Tabs>
    </div>
</template>
