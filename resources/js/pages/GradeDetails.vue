<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import {
    PageContainer,
    PageHeader,
    SectionHeader,
    StatItem,
} from '@/components/page';
import grades from '@/routes/grades';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

type Comment = {
    author: string;
    role: string;
    date: string;
    text: string;
};

const props = defineProps<{
    breadcrumb?: string;
    title?: string;
    note?: number;
    testDate?: string;
    matiere?: string;
    depositedAt?: string;
    pdfUrl?: string;
    comments?: Comment[];
}>();

const comments = ref<Comment[]>(props.comments ?? []);
const newComment = ref('');

const perspective = ref<'apprentice' | 'coach'>('coach');
const canComment = () => perspective.value === 'coach';

const roleStyles: Record<string, { dot: string; text: string }> = {
    Coach: { dot: 'bg-info', text: 'text-info' },
    Formateur: { dot: 'bg-success', text: 'text-success' },
    Apprenti: { dot: 'bg-warning', text: 'text-warning' },
};

const roleStyle = (role: string) =>
    roleStyles[role] ?? {
        dot: 'bg-muted-foreground',
        text: 'text-muted-foreground',
    };

const breadcrumbs = [{ label: 'Carnet de notes', href: grades.dashboard() }];

const submitComment = () => {
    if (!newComment.value.trim()) return;

    comments.value.push({
        author: 'Vous',
        role: perspective.value === 'coach' ? 'Coach' : 'Apprenti',
        date: new Date().toLocaleDateString('fr-CH', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }),
        text: newComment.value.trim(),
    });
    newComment.value = '';
};
</script>

<template>
    <Head :title="title ?? 'Détail de la note'" />

    <PageContainer>
        <PageHeader
            :title="title ?? 'M117 — Épreuve pratique, base de données'"
            :description="breadcrumb ?? 'Année 1 · Modules école pro'"
            :breadcrumbs="breadcrumbs"
        >
            <template #actions>
                <ToggleGroup
                    v-model="perspective"
                    type="single"
                    variant="outline"
                    size="sm"
                >
                    <ToggleGroupItem value="apprentice"
                        >Apprenti</ToggleGroupItem
                    >
                    <ToggleGroupItem value="coach">
                        Coach / Formateur
                    </ToggleGroupItem>
                </ToggleGroup>
            </template>
        </PageHeader>

        <Card>
            <CardContent class="grid grid-cols-2 gap-6 sm:grid-cols-4">
                <StatItem label="Note">
                    <p class="text-3xl font-semibold tabular-nums">
                        {{ (note ?? 5).toFixed(1) }}
                    </p>
                </StatItem>
                <StatItem label="Date du test">
                    {{ testDate ?? '22.10.2025' }}
                </StatItem>
                <StatItem label="Matière">
                    {{ matiere ?? 'M117 — Base de données' }}
                </StatItem>
                <StatItem label="Déposé le">
                    {{ depositedAt ?? '23.10.2025' }}
                </StatItem>
            </CardContent>
        </Card>

        <Card class="overflow-hidden py-0">
            <div class="bg-muted flex h-[70vh] justify-center overflow-auto">
                <p v-if="!pdfUrl" class="text-muted-foreground m-auto text-sm">
                    Aucun document déposé.
                </p>
                <embed
                    v-else
                    :src="pdfUrl"
                    type="application/pdf"
                    class="size-full"
                />
            </div>
        </Card>

        <section class="flex flex-col gap-4">
            <SectionHeader title="Commentaires" />

            <div v-if="comments.length" class="relative flex flex-col">
                <div class="bg-border absolute inset-y-2 left-[5px] w-px" />

                <div
                    v-for="(comment, index) in comments"
                    :key="index"
                    class="relative flex flex-col gap-1 py-4 pl-6 first:pt-0 last:pb-0"
                >
                    <span
                        :class="roleStyle(comment.role).dot"
                        class="absolute top-1.5 left-0 size-2.5 rounded-full"
                    />
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-medium">{{ comment.author }}</p>
                        <p class="text-muted-foreground text-xs">
                            {{ comment.date }}
                        </p>
                    </div>
                    <p
                        :class="roleStyle(comment.role).text"
                        class="text-sm font-medium"
                    >
                        {{ comment.role }}
                    </p>
                    <p class="text-sm">{{ comment.text }}</p>
                </div>
            </div>
            <p v-else class="text-muted-foreground text-sm">
                Aucun commentaire pour le moment.
            </p>

            <div v-if="canComment()" class="flex flex-col gap-2 pt-2">
                <Textarea
                    v-model="newComment"
                    placeholder="Écrire un commentaire…"
                    class="min-h-20 resize-none"
                    @keydown.meta.enter="submitComment"
                    @keydown.ctrl.enter="submitComment"
                    @keydown.enter.exact.prevent="submitComment"
                />
                <div class="flex justify-end">
                    <Button
                        size="sm"
                        :disabled="!newComment.trim()"
                        @click="submitComment"
                    >
                        Publier
                    </Button>
                </div>
            </div>
            <p v-else class="text-muted-foreground text-sm">
                Les apprentis ne peuvent pas commenter cette évaluation.
            </p>
        </section>
    </PageContainer>
</template>
