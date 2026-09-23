<script lang="ts" setup>
import { PencilIcon } from '@lucide/vue';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogClose,
} from '@/components/ui/dialog';
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
import {
    Field,
    FieldDescription,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { Separator } from '@/components/ui/separator';
import type {
    EditableSubject,
    SubjectSavePayload,
    Track,
} from '@/types/subject';

const props = defineProps<{
    subject: EditableSubject;
}>();

const emit = defineEmits<{
    (e: 'save', payload: SubjectSavePayload): void;
    (e: 'deactivate', id: number): void;
}>();

const open = ref(false);

const name = ref(props.subject.name);
const domain = ref(props.subject.domain);
const track = ref<Track>(props.subject.track);

watch(open, (isOpen) => {
    if (isOpen) {
        name.value = props.subject.name;
        domain.value = props.subject.domain;
        track.value = props.subject.track;
    }
});

const isValid = () => name.value.trim() !== '' && domain.value.trim() !== '';

function handleSubmit() {
    if (!isValid()) return;

    emit('save', {
        id: props.subject.id,
        name: name.value.trim(),
        domain: domain.value.trim(),
        track: track.value,
    });

    open.value = false;
}

function handleDeactivate() {
    emit('deactivate', props.subject.id);
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button
                variant="ghost"
                size="icon-sm"
                class="text-muted-foreground hover:text-foreground"
                :aria-label="`Modifier ${subject.name}`"
                :title="`Modifier ${subject.name}`"
            >
                <PencilIcon aria-hidden="true" />
            </Button>
        </DialogTrigger>

        <DialogContent class="sm:max-w-md">
            <form @submit.prevent="handleSubmit">
                <DialogHeader>
                    <DialogTitle>Modifier la matière</DialogTitle>
                    <DialogDescription>
                        Modifiez les informations de la matière ou désactivez-la
                        si elle n'est plus utilisée.
                    </DialogDescription>
                </DialogHeader>

                <FieldGroup class="py-4">
                    <Field>
                        <FieldLabel for="edit-subject-name">
                            Nom de la matière
                        </FieldLabel>
                        <Input id="edit-subject-name" v-model="name" required />
                    </Field>

                    <Field>
                        <FieldLabel for="edit-subject-domain">
                            Domaine / Module
                        </FieldLabel>
                        <Input
                            id="edit-subject-domain"
                            v-model="domain"
                            required
                        />
                    </Field>

                    <Field>
                        <FieldLabel>Filière</FieldLabel>
                        <ToggleGroup
                            v-model="track"
                            type="single"
                            variant="outline"
                            class="justify-start"
                        >
                            <ToggleGroupItem value="IT">IT</ToggleGroupItem>
                            <ToggleGroupItem value="EC">EC</ToggleGroupItem>
                        </ToggleGroup>
                    </Field>

                    <FieldDescription v-if="subject.hasGrades">
                        Cette matière est déjà utilisée dans au moins une note.
                        Elle ne peut pas être supprimée, mais elle peut être
                        désactivée ci-dessous.
                    </FieldDescription>
                </FieldGroup>

                <Separator class="mb-4" />

                <DialogFooter class="justify-between sm:justify-between">
                    <AlertDialog>
                        <AlertDialogTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                class="border-warning/50 text-warning hover:bg-warning/10 hover:text-warning"
                            >
                                Désactiver
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>
                                    Désactiver « {{ subject.name }} » ?
                                </AlertDialogTitle>
                                <AlertDialogDescription>
                                    La matière ne sera plus proposée pour de
                                    nouvelles notes, mais restera visible dans
                                    les carnets existants. Vous pourrez la
                                    réactiver à tout moment.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Annuler</AlertDialogCancel>
                                <AlertDialogAction
                                    class="bg-warning text-warning-foreground hover:bg-warning/90"
                                    @click="handleDeactivate"
                                >
                                    Désactiver
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>

                    <div class="flex gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">
                                Annuler
                            </Button>
                        </DialogClose>
                        <Button type="submit" :disabled="!isValid()">
                            Enregistrer
                        </Button>
                    </div>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
