<script setup lang="ts">
import {Card, CardHeader, CardTitle} from "@/components/ui/card";
defineProps({
    title: String,
    grade: String,
    weight: String
})
</script>

<template>
    <Card class="p-5 w-full h-full">
        <CardHeader class="flex flex-row justify-between p-0">
            <CardTitle>{{title}}</CardTitle>
            <Card class="flex-row items-center rounded-md px-2.5 py-0.5 text-xs font-medium shadow-none">{{weight}}</Card>
        </CardHeader>
        <CardTitle>{{grade}}</CardTitle>
    </Card>
</template>

<style scoped>

</style>
