<script setup lang="ts">
import { TableCell, TableRow } from '@/components/ui/table';
import { Trash2Icon } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import EditSubject from '@/components/subject/EditSubject.vue';
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
} from '@/components/ui/alert-dialog';
import type { Subject, SubjectSavePayload, Track } from '@/types/subject';

const props = defineProps<{
    subject: Subject;
}>();

const emit = defineEmits<{
    (e: 'save', payload: SubjectSavePayload): void;
    (e: 'deactivate', id: number): void;
    (e: 'reactivate', id: number): void;
    (e: 'delete', id: number): void;
}>();

const trackAbbreviations: Record<string, string> = {
    Informatique: 'IT',
    'Employé-e de commerce': 'EC',
};

const trackLabel = computed(
    () => trackAbbreviations[props.subject.track] ?? props.subject.track,
);

const isInactive = computed(() => props.subject.status === 'Désactivée');
</script>

<template>
    <TableRow :class="isInactive && 'text-muted-foreground'">
        <TableCell class="max-w-0 font-medium">
            <div class="flex min-w-0 items-center gap-2">
                <span class="truncate" :title="subject.name">
                    {{ subject.name }}
                </span>
                <Badge v-if="isInactive" variant="outline">Désactivée</Badge>
            </div>
        </TableCell>
        <TableCell>{{ subject.domain }}</TableCell>
        <TableCell>{{ trackLabel }}</TableCell>

        <TableCell>
            <div class="flex items-center justify-end gap-2">
                <EditSubject
                    v-if="!isInactive"
                    :subject="{
                        id: subject.id,
                        name: subject.name,
                        domain: subject.domain,
                        track: trackLabel as Track,
                        status: subject.status,
                        hasGrades: subject.hasGrades,
                    }"
                    @save="emit('save', $event)"
                    @deactivate="emit('deactivate', $event)"
                />
                <Button
                    v-if="isInactive"
                    variant="outline"
                    size="sm"
                    class="min-w-24"
                    @click="emit('reactivate', subject.id)"
                >
                    Réactiver
                </Button>

                <AlertDialog v-if="!isInactive && !subject.hasGrades">
                    <AlertDialogTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-muted-foreground hover:text-destructive"
                            :aria-label="`Supprimer ${subject.name}`"
                        >
                            <Trash2Icon aria-hidden="true" />
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
                                :class="
                                    buttonVariants({ variant: 'destructive' })
                                "
                                @click="emit('delete', subject.id)"
                            >
                                Supprimer
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
                <!--
                    Emplacement réservé : sans lui, « Modifier » se décale sur
                    les lignes où la suppression n'est pas possible.
                -->
                <span v-else class="size-8 shrink-0" aria-hidden="true" />
            </div>
        </TableCell>
    </TableRow>
</template>
