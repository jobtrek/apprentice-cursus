<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { FieldError } from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import {
    PageContainer,
    PageHeader,
    SectionHeader,
    StatItem,
} from '@/components/page';
import { findApprentice } from '@/composables/useApprentices';
import { apprentisdashboard } from '@/routes';
import apprentices from '@/routes/apprentices';
import commentRoutes from '@/routes/comments';
import grades from '@/routes/grades';
import type { Grade } from '@/types/grade';
import { Head, useForm } from '@inertiajs/vue3';
import { PencilIcon } from '@lucide/vue';
import { computed, ref } from 'vue';

type Comment = {
    id: number;
    author: string;
    role: string;
    date: string;
    text: string;
    edited: boolean;
    can: { update: boolean };
};

const props = defineProps<{
    grade: Grade;
    pdfUrl?: string | null;
    comments: Comment[];
    /** Présent quand un coach ou formateur consulte la note d'un·e apprenti·e. */
    apprenticeId?: number;
    /** Décisions d'autorisation calculées côté serveur. */
    can: { comment: boolean };
}>();

const form = useForm({ body: '' });
const editForm = useForm({ body: '' });
const editingId = ref<number | null>(null);

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

const apprentice = computed(() =>
    props.apprenticeId ? findApprentice(props.apprenticeId) : undefined,
);

const breadcrumbs = computed(() =>
    props.apprenticeId
        ? [
              { label: 'Apprentis', href: apprentisdashboard() },
              {
                  label: apprentice.value?.name ?? 'Apprenti·e',
                  href: apprentices.show(props.apprenticeId),
              },
          ]
        : [{ label: 'Carnet de notes', href: grades.dashboard() }],
);

const submitComment = () => {
    if (!props.can.comment || !form.body.trim() || form.processing) return;

    form.post(grades.comments.store(props.grade.id).url, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const startEdit = (comment: Comment) => {
    editForm.clearErrors();
    editForm.body = comment.text;
    editingId.value = comment.id;
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const submitEdit = (comment: Comment) => {
    if (!editForm.body.trim() || editForm.processing) return;

    editForm.put(commentRoutes.update(comment.id).url, {
        preserveScroll: true,
        onSuccess: cancelEdit,
    });
};
</script>

<template>
    <Head :title="grade.title" />

    <PageContainer>
        <PageHeader
            :title="grade.title"
            :description="`${grade.subject} · Semestre ${grade.semester}`"
            :breadcrumbs="breadcrumbs"
        />

        <Card>
            <CardContent class="grid grid-cols-2 gap-6 sm:grid-cols-3">
                <StatItem label="Note">
                    <p class="text-3xl font-semibold tabular-nums">
                        {{ grade.value.toFixed(1) }}
                    </p>
                </StatItem>
                <StatItem label="Date du test">
                    {{ grade.date }}
                </StatItem>
                <StatItem label="Matière">
                    {{ grade.subject }}
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
                    v-for="comment in comments"
                    :key="comment.id"
                    class="relative flex flex-col gap-1 py-4 pl-6 first:pt-0 last:pb-0"
                >
                    <span
                        :class="roleStyle(comment.role).dot"
                        class="absolute top-1.5 left-0 size-2.5 rounded-full"
                    />
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex flex-col gap-1">
                            <p class="font-medium">{{ comment.author }}</p>
                            <p
                                :class="roleStyle(comment.role).text"
                                class="text-sm font-medium"
                            >
                                {{ comment.role }}
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <p class="text-muted-foreground text-xs">
                                {{ comment.date }}
                            </p>
                            <Button
                                v-if="
                                    comment.can.update &&
                                    editingId !== comment.id
                                "
                                variant="outline"
                                size="icon-sm"
                                aria-label="Modifier le commentaire"
                                title="Modifier le commentaire"
                                @click="startEdit(comment)"
                            >
                                <PencilIcon aria-hidden="true" />
                            </Button>
                        </div>
                    </div>
                    <div
                        v-if="editingId === comment.id"
                        class="flex flex-col gap-2"
                    >
                        <Textarea
                            v-model="editForm.body"
                            maxlength="2000"
                            class="min-h-20 resize-none"
                            @keydown.meta.enter="submitEdit(comment)"
                            @keydown.ctrl.enter="submitEdit(comment)"
                            @keydown.enter.exact.prevent="submitEdit(comment)"
                            @keydown.esc="cancelEdit"
                        />
                        <FieldError :errors="[editForm.errors.body]" />
                        <div class="flex justify-end gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="cancelEdit"
                            >
                                Annuler
                            </Button>
                            <Button
                                size="sm"
                                :disabled="
                                    !editForm.body.trim() || editForm.processing
                                "
                                @click="submitEdit(comment)"
                            >
                                Enregistrer
                            </Button>
                        </div>
                    </div>
                    <template v-else>
                        <p class="text-sm">
                            {{ comment.text }}
                            <span
                                v-if="comment.edited"
                                class="text-muted-foreground text-xs"
                            >
                                (modifié)
                            </span>
                        </p>
                    </template>
                </div>
            </div>
            <p v-else class="text-muted-foreground text-sm">
                Aucun commentaire pour le moment.
            </p>

            <div v-if="can.comment" class="flex flex-col gap-2 pt-2">
                <Textarea
                    v-model="form.body"
                    placeholder="Écrire un commentaire…"
                    maxlength="2000"
                    class="min-h-20 resize-none"
                    @keydown.meta.enter="submitComment"
                    @keydown.ctrl.enter="submitComment"
                    @keydown.enter.exact.prevent="submitComment"
                />
                <FieldError :errors="[form.errors.body]" />
                <div class="flex justify-end">
                    <Button
                        size="sm"
                        :disabled="!form.body.trim() || form.processing"
                        @click="submitComment"
                    >
                        Publier
                    </Button>
                </div>
            </div>
            <p v-else class="text-muted-foreground text-sm">
                Seuls les coachs et formateurs peuvent commenter cette
                évaluation.
            </p>
        </section>
    </PageContainer>
</template>
