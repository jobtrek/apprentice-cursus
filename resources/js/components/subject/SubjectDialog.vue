<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import FilterSelect from '@/components/FilterSelect.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import subjects from '@/routes/subjects';
import type { Subject, SubjectParent } from '@/types/subject';

const props = defineProps<{
    parents: SubjectParent[];
    subject?: Subject;
}>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm({
    name: '',
    parent_id: '',
    weight: '10',
});

watch(open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.clearErrors();
        if (props.subject) {
            form.name = props.subject.name;
            form.parent_id = String(props.subject.parent_id);
            form.weight = String(props.subject.weight);
        }
    }
});

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    };

    if (props.subject) {
        form.put(subjects.update.url(props.subject.id), options);
    } else {
        form.post(subjects.store.url(), options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <form novalidate @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            subject ? 'Modifier la matière' : 'Nouvelle matière'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            subject
                                ? subject.parent
                                : "Elle entre dans la moyenne du domaine choisi et s'affiche dans le formulaire de note."
                        }}
                    </DialogDescription>
                </DialogHeader>

                <FieldGroup class="py-4">
                    <Field :data-invalid="Boolean(form.errors.name)">
                        <FieldLabel for="subject-name">
                            Nom de la matière
                        </FieldLabel>
                        <Input
                            id="subject-name"
                            v-model="form.name"
                            required
                            :aria-invalid="Boolean(form.errors.name)"
                        />
                        <FieldError :errors="[form.errors.name]" />
                    </Field>

                    <Field :data-invalid="Boolean(form.errors.parent_id)">
                        <FieldLabel>Domaine</FieldLabel>
                        <FilterSelect
                            v-model="form.parent_id"
                            :options="parents"
                            label="Domaine de la matière"
                            class="sm:w-full"
                        />
                        <FieldError :errors="[form.errors.parent_id]" />
                    </Field>

                    <Field :data-invalid="Boolean(form.errors.weight)">
                        <FieldLabel for="subject-weight">
                            Pondération (%)
                        </FieldLabel>
                        <Input
                            id="subject-weight"
                            v-model="form.weight"
                            type="number"
                            min="1"
                            max="100"
                            step="1"
                            required
                            :aria-invalid="Boolean(form.errors.weight)"
                        />
                        <FieldDescription>
                            Part dans la moyenne du domaine. Les pondérations
                            sont normalisées par leur somme.
                        </FieldDescription>
                        <FieldError :errors="[form.errors.weight]" />
                    </Field>
                </FieldGroup>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline">
                            Annuler
                        </Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        data-test="save-subject-button"
                    >
                        {{ subject ? 'Enregistrer' : 'Ajouter la matière' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
