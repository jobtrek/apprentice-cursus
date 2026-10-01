<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { SupervisorOption } from '@/types/apprentice';

const props = defineProps<{
    /** Superviseur actuel, null si aucun. */
    current: SupervisorOption | null;
    options: SupervisorOption[];
    /** Route PUT qui enregistre le choix. */
    url: string;
    /** Champ envoyé. */
    field: 'coach_id';
    /** Libellé de l'option vide, ex. « Aucun coach ». */
    noneLabel: string;
    /** Nom accessible du champ, ex. « Coach de Léa Dubois ». */
    label: string;
}>();

/** Valeur du Select pour « aucun » (un SelectItem ne peut pas valoir ''). */
const NONE = 'none';
const saving = ref(false);

function save(value: unknown): void {
    const id = value === NONE ? null : Number(value);

    if (id === (props.current?.id ?? null)) {
        return;
    }

    router.put(
        props.url,
        { [props.field]: id },
        {
            preserveScroll: true,
            onStart: () => (saving.value = true),
            onFinish: () => (saving.value = false),
        },
    );
}
</script>

<template>
    <!-- Admin local : choisir, changer ou retirer le superviseur d'un·e apprenti·e. -->
    <Select
        :model-value="current ? String(current.id) : NONE"
        :disabled="saving"
        @update:model-value="save"
    >
        <SelectTrigger size="sm" class="w-44" :aria-label="label">
            <SelectValue />
        </SelectTrigger>
        <SelectContent>
            <SelectItem :value="NONE">{{ noneLabel }}</SelectItem>
            <SelectItem
                v-for="option in options"
                :key="option.id"
                :value="String(option.id)"
            >
                {{ option.name }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
