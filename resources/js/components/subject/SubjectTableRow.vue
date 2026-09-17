<script setup lang="ts">
import { TableCell, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import EditSubject from "@/components/subject/EditSubject.vue";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@/components/ui/alert-dialog";

const props = defineProps<{
    subject: {
        id: number;
        name: string;
        domain: string;
        track: string;
        status: "Active" | "Désactivée";
        hasGrades: boolean;
    };
}>();

const emit = defineEmits<{
    (
        e: "save",
        payload: {
            id: number;
            name: string;
            domain: string;
            track: "IT" | "EC";
        },
    ): void;
    (e: "deactivate", id: number): void;
    (e: "reactivate", id: number): void;
    (e: "delete", id: number): void;
}>();

const trackAbbreviations: Record<string, string> = {
    Informatique: "IT",
    "Employé-e de commerce": "EC",
};

const trackLabel =
    trackAbbreviations[props.subject.track] ?? props.subject.track;
</script>

<template>
    <TableRow
        :class="subject.status === 'Désactivée' ? 'text-muted-foreground' : ''"
    >
        <TableCell
            class="font-medium max-w-0"
            :class="subject.status === 'Active' ? 'text-card-foreground' : ''"
        >
            <span class="block truncate" :title="subject.name">{{
                subject.name
            }}</span>
        </TableCell>
        <TableCell>{{ subject.domain }}</TableCell>
        <TableCell>{{ trackLabel }}</TableCell>

        <TableCell class="text-right">
            <div class="flex items-center gap-2">
                <EditSubject
                    v-if="subject.status === 'Active'"
                    :subject="{
                        id: subject.id,
                        name: subject.name,
                        domain: subject.domain,
                        track: trackLabel as 'IT' | 'EC',
                        status: subject.status,
                        hasGrades: subject.hasGrades,
                    }"
                    @save="emit('save', $event)"
                    @deactivate="emit('deactivate', $event)"
                />

                <AlertDialog v-if="subject.status === 'Active'">
                    <AlertDialogTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8 text-muted-foreground hover:text-destructive justify-center"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M3 6h18" />
                                <path
                                    d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"
                                />
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                            </svg>
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                Supprimer « {{ subject.name }} » ?
                            </AlertDialogTitle>
                            <AlertDialogDescription>
                                Cette action est irréversible. La matière sera
                                définitivement supprimée de la liste.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Annuler</AlertDialogCancel>
                            <AlertDialogAction
                                class="bg-destructive hover:bg-destructive/90 text-white"
                                @click="emit('delete', subject.id)"
                            >
                                Supprimer
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>

                <Button
                    v-if="subject.status === 'Désactivée'"
                    variant="default"
                    size="sm"
                    class="bg-emerald-600 hover:bg-emerald-500 text-white"
                    @click="emit('reactivate', subject.id)"
                >
                    Réactiver
                </Button>
            </div>
        </TableCell>
    </TableRow>
</template>
