<script setup lang="ts">
import { DialogClose } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
</script>

<template>
    <DialogClose as-child>
        <Button type="button" variant="outline">
            <slot>Annuler</slot>
        </Button>
    </DialogClose>
</template>
