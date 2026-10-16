<script setup lang="ts">
import { SearchIcon } from '@lucide/vue';
import { useId } from 'vue';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';

withDefaults(
    defineProps<{
        placeholder?: string;
        class?: string;
    }>(),
    { placeholder: 'Rechercher…' },
);

const search = defineModel<string>({ required: true });
const id = useId();
</script>

<template>
    <InputGroup :class="$props.class">
        <label :for="id" class="sr-only">{{ placeholder }}</label>
        <InputGroupAddon>
            <SearchIcon aria-hidden="true" />
        </InputGroupAddon>
        <InputGroupInput
            :id="id"
            v-model="search"
            type="search"
            :placeholder="placeholder"
        />
    </InputGroup>
</template>
