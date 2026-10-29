<script setup lang="ts">
import { FieldLabel } from '@/components/ui/field';
import { Switch } from '@/components/ui/switch';

defineProps<{
    id: string;
    label: string;
}>();

const model = defineModel<boolean>({ required: true });
</script>

<template>
    <div class="flex items-center gap-2">
        <Switch :id="id" v-model="model" />
        <FieldLabel :for="id" class="font-normal">{{ label }}</FieldLabel>
    </div>
</template>
