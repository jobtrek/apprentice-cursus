<script setup lang="ts">
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import AssignmentBadge from "./AssignmentBadge.vue";

defineProps<{
    coach?: string;
    formateur?: string;
}>();
</script>

<template>
    <div class="flex items-center gap-10">
        <div>
            <p
                class="text-[11px] uppercase tracking-wide text-muted-foreground mb-1.5"
            >
                Coach
            </p>
            <div class="flex items-center gap-2">
                <Avatar class="size-7">
                    <AvatarFallback class="text-xs">
                        {{ (coach ?? "Non assigné").charAt(0) }}
                    </AvatarFallback>
                </Avatar>
                <AssignmentBadge :value="coach" />
            </div>
        </div>

        <div>
            <p
                class="text-[11px] uppercase tracking-wide text-muted-foreground mb-1.5"
            >
                Formateur
            </p>
            <AssignmentBadge :value="formateur" />
        </div>
    </div>
</template>
