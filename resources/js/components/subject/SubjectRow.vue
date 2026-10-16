<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { PencilIcon, Trash2Icon } from '@lucide/vue';
import TrackBadges from '@/components/apprentice/TrackBadges.vue';
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
import { Button, buttonVariants } from '@/components/ui/button';
import { TableCell, TableRow } from '@/components/ui/table';
import subjects from '@/routes/subjects';
import type { Subject } from '@/types/subject';

const props = defineProps<{
    subject: Subject;
}>();

const emit = defineEmits<{
    edit: [subject: Subject];
}>();

function destroy(): void {
    router.delete(subjects.destroy.url(props.subject.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <TableRow>
        <TableCell class="max-w-0 font-medium">
            <span class="block truncate" :title="subject.name">
                {{ subject.name }}
            </span>
        </TableCell>
        <TableCell class="text-muted-foreground">
            {{ subject.parent }}
        </TableCell>
        <TableCell>
            <div class="flex flex-wrap gap-1.5">
                <TrackBadges
                    v-for="track in subject.tracks"
                    :key="track"
                    :track="track"
                    :is-mp="false"
                />
            </div>
        </TableCell>
        <TableCell class="text-right tabular-nums">
            {{ subject.grades_count }}
        </TableCell>
        <TableCell>
            <div class="flex items-center justify-end gap-2">
                <Button
                    variant="ghost"
                    size="icon-sm"
                    class="text-muted-foreground hover:text-foreground"
                    :aria-label="`Modifier ${subject.name}`"
                    :title="`Modifier ${subject.name}`"
                    @click="emit('edit', subject)"
                >
                    <PencilIcon aria-hidden="true" />
                </Button>

                <AlertDialog v-if="subject.can_delete">
                    <AlertDialogTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-muted-foreground hover:text-destructive"
                            :aria-label="`Supprimer ${subject.name}`"
                            :title="`Supprimer ${subject.name}`"
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
                                retirée des arbres de notes.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Annuler</AlertDialogCancel>
                            <AlertDialogAction
                                :class="
                                    buttonVariants({ variant: 'destructive' })
                                "
                                @click="destroy"
                            >
                                Supprimer
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
                <!-- Emplacement réservé : « Modifier » ne se décale pas. -->
                <span v-else class="size-8 shrink-0" aria-hidden="true" />
            </div>
        </TableCell>
    </TableRow>
</template>
