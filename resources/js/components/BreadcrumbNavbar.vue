<script setup lang="ts">
import {Breadcrumb, BreadcrumbItem, BreadcrumbPage, BreadcrumbSeparator, BreadcrumbList} from "@/components/ui/breadcrumb";

const props = defineProps<{
    currentPage: string,
    pastPages: string[],
}>()
</script>

<template>
    <Breadcrumb>
        <BreadcrumbList class="text-base px-3">
            <BreadcrumbItem v-for="page in pastPages" class="text-muted-foreground/60">{{ page }}</BreadcrumbItem>
            <BreadcrumbSeparator v-if="pastPages"/>
            <BreadcrumbPage class="text-muted-foreground/70">{{ currentPage }}</BreadcrumbPage>
        </BreadcrumbList>
    </Breadcrumb>
</template>
