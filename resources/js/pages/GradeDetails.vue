<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { Link } from '@inertiajs/vue3';
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

const roleStyles: Record<string, string> = {
    Coach: 'bg-blue-500 text-blue-500',
    Formateur: 'bg-emerald-500 text-emerald-500',
    Apprenti: 'bg-amber-500 text-amber-500',
};

const roleStyle = (role: string) =>
    roleStyles[role] ?? 'bg-muted-foreground text-muted-foreground';

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
    <div class="mx-auto mt-6 flex w-full max-w-3xl flex-col gap-4">
        <div class="flex flex-col gap-1">
            <Link
                href="/"
                class="text-muted-foreground text-sm hover:underline"
            >
                {{
                    breadcrumb ??
                    'Carnet de notes · Année 1 · Modules école pro'
                }}
            </Link>
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold">
                    {{ title ?? 'M117 — Épreuve pratique, base de données' }}
                </h1>

                <ToggleGroup
                    v-model="perspective"
                    type="single"
                    variant="outline"
                    size="sm"
                    class="shrink-0"
                >
                    <ToggleGroupItem value="apprentice">
                        Apprenti
                    </ToggleGroupItem>
                    <ToggleGroupItem value="coach">
                        Coach / Formateur
                    </ToggleGroupItem>
                </ToggleGroup>
            </div>
        </div>

        <Card>
            <CardContent class="grid grid-cols-4 gap-6">
                <div>
                    <p
                        class="text-muted-foreground text-xs font-medium tracking-wide uppercase"
                    >
                        Note
                    </p>
                    <p class="text-2xl font-semibold">
                        {{ (note ?? 5).toFixed(1) }}
                    </p>
                </div>
                <div>
                    <p
                        class="text-muted-foreground text-xs font-medium tracking-wide uppercase"
                    >
                        Date du test
                    </p>
                    <p>{{ testDate ?? '22.10.2025' }}</p>
                </div>
                <div>
                    <p
                        class="text-muted-foreground text-xs font-medium tracking-wide uppercase"
                    >
                        Matière
                    </p>
                    <p>{{ matiere ?? 'M117 — Base de données' }}</p>
                </div>
                <div>
                    <p
                        class="text-muted-foreground text-xs font-medium tracking-wide uppercase"
                    >
                        Déposé le
                    </p>
                    <p>{{ depositedAt ?? '23.10.2025' }}</p>
                </div>
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
                    class="h-full w-full"
                />
            </div>
        </Card>

        <div class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold">Commentaires</h2>

            <div v-if="comments.length" class="relative flex flex-col">
                <div
                    class="bg-border absolute top-2 bottom-2 left-[5px] w-px"
                />

                <div
                    v-for="(comment, index) in comments"
                    :key="index"
                    class="relative flex flex-col gap-1 py-4 pl-6 first:pt-0 last:pb-0"
                >
                    <span
                        :class="roleStyle(comment.role).split(' ')[0]"
                        class="absolute top-1.5 left-0 size-2.5 rounded-full"
                    />
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-medium">{{ comment.author }}</p>
                        <p class="text-muted-foreground text-xs">
                            {{ comment.date }}
                        </p>
                    </div>
                    <p
                        :class="roleStyle(comment.role).split(' ')[1]"
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
            <p v-else class="text-muted-foreground text-xs">
                Les apprentis ne peuvent pas commenter cette évaluation.
            </p>
        </div>
    </div>
</template>
