<script setup lang="ts">
import {Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator} from "@/components/ui/breadcrumb";
import BreadcrumbNavbar from "@/components/BreadcrumbNavbar.vue";

const props = defineProps<{
    pageTitle: string
    currentPage: string,
    pastPages: string[],
}>()
</script>

<template>
    <header class="mb-4">

        <div class="flex flex-col gap-3">
            <h1 class="text-4xl font-bold">{{ pageTitle }}</h1>
            <BreadcrumbNavbar :current-page="currentPage" :past-pages="pastPages" />
        </div>
    </header>
</template>
