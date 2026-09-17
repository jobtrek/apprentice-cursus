<script setup lang="ts">
import {Card, CardContent, CardDescription, CardHeader, CardTitle} from "@/components/ui/card";
import {Accordion} from "@/components/ui/accordion";
import DomainCards from "@/components/DomainCards.vue";
import GradeListLayout from "@/components/gradeList/GradeListLayout.vue";
import GradeAccordionItem from "@/components/gradeList/GradeAccordionItem.vue";
import GradeHeader from "@/components/gradeList/GradeHeader.vue";
import type { Grade } from "@/types/grade";

const apprenticeName = "Léa Bertrand"
const filiere = "Filière Informatique"
const cohort = "Volée 2024–2027"
const pageTitle = "Carnet de notes"

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
    { title: "Test 1", subject: "Mathématique", value: 6, semester: 1, date: "12.03.2026" },
    { title: "Test 1", subject: "Mathématique", value: 6, semester: 1, date: "12.03.2026" },


]
const gradeTables = [
    {
        title: "Compétences en informatiques",
        columns,
        grades: gradeRows,
        subMenu: [
            { title: "Modules école pro", columns, grades: gradeRows },
            { title: "Modules CIE", columns, grades: gradeRows },
        ],
    },
    { title: "Compétence de base élargies", columns, grades: gradeRows },
    { title: "Culture générale", columns, grades: gradeRows },
    { title: "TPI", columns, grades: gradeRows },
]
</script>

<template>
    <div class="w-full max-w-7xl mx-auto py-6">

        <GradeHeader
            :apprentice-name="apprenticeName"
            :filiere="filiere"
            :cohort="cohort"
            :page-title="pageTitle"
        />

        <article class="grid grid-cols-4 grid-rows-[auto_auto_1fr] gap-3">
            <DomainCards class="col-span-4" title="Note finale CFC" :grade="5" weight="100%" />
            <div v-for="domain in domainInformations" class="flex flex-row">
                <DomainCards :title="domain.title" :grade="domain.grade" :weight="domain.weight" />
          </div>
            <GradeListLayout class="row-start-3 col-span-4">
                <Accordion type="multiple">
                    <GradeAccordionItem
                        v-for="table in gradeTables"
                        :key="table.title"
                        :menu="table"
                    />
                </Accordion>
            </GradeListLayout>
        </article>
    </div>
</template>
