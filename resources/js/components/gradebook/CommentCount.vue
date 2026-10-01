<script setup lang="ts">
import { MessageSquareIcon } from '@lucide/vue';

defineProps<{ count?: number }>();
</script>

<template>
    <span v-if="count" :title="`${count} commentaire${count > 1 ? 's' : ''}`">
        <MessageSquareIcon aria-hidden="true" />
        {{ count }}
    </span>
</template>
