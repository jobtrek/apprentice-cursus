<script setup lang="ts">
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";

const track = defineModel<"All" | "IT" | "EC">({ required: true });
</script>

<template>
    <Tabs v-model="track" class="w-full mb-6">
        <TabsList
            class="w-full grid grid-cols-3 h-12 rounded-xl bg-muted border"
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
