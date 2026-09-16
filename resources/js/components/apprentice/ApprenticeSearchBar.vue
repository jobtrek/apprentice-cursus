<script setup lang="ts">
import { InputGroup, InputGroupInput } from "@/components/ui/input-group";

const search = defineModel<string>({ required: true });
</script>

<template>
    <InputGroup class="mb-6">
        <svg
            data-icon="inline-start"
            xmlns="http://www.w3.org/2000/svg"
            class="size-4"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <circle cx="11" cy="11" r="8" />
            <line x1="21" y1="21" x2="16.65" y2="16.65" />
        </svg>
        <InputGroupInput
            v-model="search"
            placeholder="Rechercher un·e apprenti·e"
        />
    </InputGroup>
</template>
