<script setup lang="ts">
import { SearchIcon } from '@lucide/vue';
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
</script>

<template>
    <InputGroup :class="$props.class">
        <InputGroupAddon>
            <SearchIcon aria-hidden="true" />
        </InputGroupAddon>
        <InputGroupInput
            v-model="search"
            type="search"
            :placeholder="placeholder"
            :aria-label="placeholder"
        />
    </InputGroup>
</template>
