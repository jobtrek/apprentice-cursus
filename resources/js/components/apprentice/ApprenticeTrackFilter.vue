<script setup lang="ts">
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";

const track = defineModel<"All" | "IT" | "EC">({ required: true });
</script>

<template>
    <Tabs v-model="track" class="mb-6 w-full">
        <TabsList
            class="grid h-12 w-full grid-cols-3 rounded-xl border bg-muted"
        >
            <TabsTrigger value="All" class="rounded-lg text-base"
                >All</TabsTrigger
            >
            <TabsTrigger value="IT" class="rounded-lg text-base"
                >IT</TabsTrigger
            >
            <TabsTrigger value="EC" class="rounded-lg text-base"
                >EC</TabsTrigger
            >
        </TabsList>
    </Tabs>
</template>
