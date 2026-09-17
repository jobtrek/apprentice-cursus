<script setup lang="ts">
import { TableCell, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";

const props = defineProps<{
    subject: {
        id: number;
        name: string;
        domain: string;
        track: string;
        status: "Active" | "Désactivée";
        hasGrades: boolean;
    };
}>();

const trackAbbreviations: Record<string, string> = {
    Informatique: "IT",
    "Employé-e de commerce": "EC",
};

const trackLabel = trackAbbreviations[props.subject.track] ?? props.subject.track;
</script>

<template>
    <TableRow
        :class="subject.status === 'Désactivée' ? 'text-muted-foreground' : ''"
    >
        <TableCell
            class="font-medium max-w-0"
            :class="subject.status === 'Active' ? 'text-card-foreground' : ''"
        >
            <span class="block truncate" :title="subject.name">{{
                subject.name
            }}</span>
        </TableCell>
        <TableCell>{{ subject.domain }}</TableCell>
        <TableCell>{{ trackLabel }}</TableCell>

        <TableCell class="text-right">
            <div class="flex items-center gap-2">
                <Button
                    v-if="subject.status === 'Active'"
                    variant="outline"
                    size="sm"
                >
                    Modifier
                </Button>

                <Button
                    v-if="subject.status === 'Active'"
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8 text-muted-foreground hover:text-foreground justify-end"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M3 6h18" />
                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                    </svg>
                </Button>

                <Button
                    v-if="subject.status === 'Désactivée'"
                    variant="default"
                    size="sm"
                    class="bg-emerald-600 hover:bg-emerald-500 text-white"
                >
                    Réactiver
                </Button>
            </div>
        </TableCell>
    </TableRow>
</template>