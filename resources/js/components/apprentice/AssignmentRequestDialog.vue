<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
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
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useAssignmentRequests } from '@/composables/useAssignmentRequests';
import type { ApprenticeListItem, SupervisorOption } from '@/types/apprentice';

const props = defineProps<{
    apprentice: ApprenticeListItem | null;
    /** Les autres coachs. */
    validators: SupervisorOption[];
}>();

const open = defineModel<boolean>('open', { required: true });

const page = usePage();
const { request } = useAssignmentRequests();

const validatorId = ref<string>();

// Chaque ouverture repart sans validateur choisi.
watch(open, (isOpen) => {
    if (isOpen) {
        validatorId.value = undefined;
    }
});

function submit(): void {
    const validator = props.validators.find(
        ({ id }) => String(id) === validatorId.value,
    );

    if (!props.apprentice || !validator) {
        return;
    }

    request(props.apprentice, validator, page.props.auth.user.name);
    open.value = false;
}
</script>

<template>
    <!-- Un coach demande à un validateur de le confirmer comme coach. -->
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Demande d'attribution</DialogTitle>
                <DialogDescription>
                    Le validateur choisi reçoit une notification et confirme
                    l'attribution.
                </DialogDescription>
            </DialogHeader>

            <form id="assignment-request-form" @submit.prevent="submit">
                <FieldGroup>
                    <Field>
                        <FieldLabel for="assignment-apprentice">
                            Apprenti·e
                        </FieldLabel>
                        <Input
                            id="assignment-apprentice"
                            :model-value="apprentice?.name"
                            readonly
                        />
                    </Field>
                    <Field>
                        <FieldLabel for="assignment-validator">
                            Validateur
                        </FieldLabel>
                        <Select v-model="validatorId" required>
                            <SelectTrigger
                                id="assignment-validator"
                                class="w-full"
                                data-test="assignment-validator"
                            >
                                <SelectValue
                                    placeholder="Choisir un validateur"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="validator in validators"
                                    :key="validator.id"
                                    :value="String(validator.id)"
                                >
                                    {{ validator.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </Field>
                </FieldGroup>
            </form>

            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Annuler</Button>
                </DialogClose>
                <Button
                    type="submit"
                    form="assignment-request-form"
                    :disabled="!validatorId"
                >
                    Envoyer la demande
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
