<script setup lang="ts">
import {Card, CardContent, CardDescription, CardHeader, CardTitle} from "@/components/ui/card";
import DomainCards from "@/components/DomainCards.vue";
import GradeListLayout from "@/components/gradeList/GradeListLayout.vue";

class DomainAverage {
    constructor(public title: string, public weight: string, public grade: number ){
    }
}

const tpi = new DomainAverage("TPI", "40%", 5.0)
const computerScienceSkills = new DomainAverage("Compétences en informatiques", "30%", 5.0)
const expandedBasicSkills = new DomainAverage("Compétence de base élargies", "10%", 4.5)
const generalEducation = new DomainAverage("Culture générale", "30%", 5.5)

const domainInformations = [tpi, computerScienceSkills, expandedBasicSkills, generalEducation]

const DATE = "Date"
const SEMESTER = "Semestre"
const GRADE_VALUE = "Note"
const MODULE = "Module"
const SUBJECT = "Matière"

const columns = [MODULE, SUBJECT, GRADE_VALUE, SEMESTER, DATE]

const gradeTables = [
    { title: "Compétence en informatique", columns },
    { title: "Culture générale", columns },
]
</script>

<template>
    <article class="grid grid-cols-4 grid-rows-[auto_auto_1fr] w-full max-w-7xl mx-auto py-6 gap-3">
        <Card class="col-span-4">
            <CardHeader class="flex flex-row">
                <CardTitle>Note finale CFC</CardTitle>
                <CardDescription>100%</CardDescription>
            </CardHeader>
            <CardContent>5</CardContent>
        </Card>
      <div v-for="domain in domainInformations" class="flex flex-row">
            <DomainCards :title="domain.title" :grade="domain.grade" :weight="domain.weight" />
      </div>
        <GradeListLayout class="row-start-3 col-span-4" :tables="gradeTables" />
    </article>
</template>
