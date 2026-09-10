<script setup lang="ts">
import {Card, CardContent, CardDescription, CardHeader, CardTitle} from "@/components/ui/card";

class DomainAverage {
    constructor(public title: string, public weight: string, public grade: number ){
    }
}

const tpi = new DomainAverage("TPI", "40%", 5.0)
const computerScienceSkills = new DomainAverage("Compétences en informatiques", "30%", 5.0)
const expandedBasicSkills = new DomainAverage("Compétence de base élargies", "10%", 4.5)
const generalEducation = new DomainAverage("Culture générale", "30%", 5.5)

const domainInformations = [tpi, computerScienceSkills, expandedBasicSkills, generalEducation]
</script>

<template>
    <section class="grid grid-cols-4 grid-rows-3 w-full">
        <Card class="col-span-4">
            <CardHeader class="flex flex-row">
                <CardTitle>Note finale CFC</CardTitle>
                <CardDescription>100%</CardDescription>
            </CardHeader>
            <CardContent>5</CardContent>
        </Card>
      <div v-for="domain in domainInformations" class="flex flex-row">
            <Card class="p-5 w-full h-full">
                <CardHeader class="flex flex-row justify-between p-0">
                    <CardTitle>{{domain.title}}</CardTitle>
                    <Card class="flex-row items-center rounded-md px-2.5 py-0.5 text-xs font-medium shadow-none">{{domain.weight}}</Card>
                </CardHeader>
                <CardTitle>{{domain.grade}}</CardTitle>
            </Card>
        </div>
    </section>
</template>
