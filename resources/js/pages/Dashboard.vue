<script setup lang="ts">
import {Card, CardContent, CardDescription, CardHeader, CardTitle} from "@/components/ui/card";
import DomainCards from "@/components/DomainCards.vue";
import GradeListLayout from "@/components/gradeList/GradeListLayout.vue";
import type { Grade } from "@/types/grade";

const tpi = { title: "TPI", weight: "40%", grade: 5.0 }
const computerScienceSkills = { title: "Compétences en informatiques", weight: "30%", grade: 5.0 }
const expandedBasicSkills = { title: "Compétence de base élargies", weight: "10%", grade: 4.5 }
const generalEducation = { title: "Culture générale", weight: "30%", grade: 5.5 }

const domainInformations = [tpi, computerScienceSkills, expandedBasicSkills, generalEducation]

const DATE = "Date"
const SEMESTER = "Semestre"
const GRADE_VALUE = "Note"
const MODULE = "Module"
const SUBJECT = "Matière"

const columns = [MODULE, SUBJECT, GRADE_VALUE, SEMESTER, DATE]

const gradeRows: Grade[] = [
    { title: "Test 1", subject: "Mathématique", value: 6, semester: 1, date: "12.03.2026" },
]
const gradeTables = [
    { title: "Compétences en informatiques", columns, grades: gradeRows },
    { title: "Compétence de base élargies", columns, grades: gradeRows },
    { title: "Culture générale", columns, grades: gradeRows },
    { title: "TPI", columns, grades: gradeRows },
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
