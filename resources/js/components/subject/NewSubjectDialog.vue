<script lang="ts" setup>
import { PlusIcon } from '@lucide/vue';
import { ref } from 'vue';
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
    Field,
    FieldDescription,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';

export type NewSubjectPayload = {
    name: string;
    domain: string;
    track: 'IT' | 'EC';
};

const emit = defineEmits<{
    (e: 'create', payload: NewSubjectPayload): void;
}>();

const open = ref(false);

const name = ref('');
const domain = ref('');
const track = ref<'IT' | 'EC'>('IT');

const isValid = () => name.value.trim() !== '' && domain.value.trim() !== '';

function resetForm() {
    name.value = '';
    domain.value = '';
    track.value = 'IT';
}

function handleSubmit() {
    if (!isValid()) return;

    emit('create', {
        name: name.value.trim(),
        domain: domain.value.trim(),
        track: track.value,
    });

    resetForm();
    open.value = false;
}

function handleOpenChange(value: boolean) {
    open.value = value;
    if (!value) resetForm();
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogTrigger as-child>
            <Button>
                <PlusIcon aria-hidden="true" />
                Nouvelle matière
            </Button>
        </DialogTrigger>

        <DialogContent class="sm:max-w-md">
            <form @submit.prevent="handleSubmit">
                <DialogHeader>
                    <DialogTitle>Nouvelle matière</DialogTitle>
                    <DialogDescription>
                        Renseignez les informations de la matière pour l'ajouter
                        à la liste.
                    </DialogDescription>
                </DialogHeader>

                <FieldGroup class="py-4">
                    <Field>
                        <FieldLabel for="subject-name">
                            Nom de la matière
                        </FieldLabel>
                        <Input
                            id="subject-name"
                            v-model="name"
                            placeholder="ex. M117 — Développer une application"
                            required
                        />
                    </Field>

                    <Field>
                        <FieldLabel for="subject-domain">
                            Domaine / Module
                        </FieldLabel>
                        <Input
                            id="subject-domain"
                            v-model="domain"
                            placeholder="ex. Module école pro"
                            required
                        />
                        <FieldDescription>
                            ex. Module CIE, DCO B, Langues, Compétences de base
                        </FieldDescription>
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
                </FieldGroup>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline">
                            Annuler
                        </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="!isValid()">
                        Ajouter la matière
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
