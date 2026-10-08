<script lang="ts" setup>
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import StatusChip from '@/components/gradebook/StatusChip.vue';
import { Card, CardContent } from '@/components/ui/card';
import { FieldError } from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import {
    PageContainer,
    PageHeader,
    SectionHeader,
    StatItem,
} from '@/components/page';
import { getInitials } from '@/composables/useInitials';
import { PASSING_GRADE } from '@/data/dashboard';
import { cn } from '@/lib/utils';
import { apprentisdashboard } from '@/routes';
import apprentices from '@/routes/apprentices';
import commentRoutes from '@/routes/comments';
import grades from '@/routes/grades';
import type { Apprentice } from '@/types/apprentice';
import type { Grade } from '@/types/grade';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    ExternalLinkIcon,
    FileXIcon,
    MessageSquareIcon,
    PencilIcon,
    SendHorizontalIcon,
} from '@lucide/vue';
import { computed, ref } from 'vue';

type Comment = {
    id: number;
    author: string;
    role: string;
    date: string;
    text: string;
    edited: boolean;
    /** Écrit par l'utilisateur connecté : affiché de l'autre côté du fil. */
    mine: boolean;
    can: { update: boolean };
};

const props = defineProps<{
    grade: Grade;
    pdfUrl?: string | null;
    comments: Comment[];
    /** Présent quand un coach ou formateur consulte la note d'un·e apprenti·e. */
    apprenticeId?: number;
    /** L'apprenti·e de la note, avec `apprenticeId`. */
    apprentice?: Apprentice;
    /** Décisions d'autorisation calculées côté serveur. */
    can: { comment: boolean };
}>();

const form = useForm({ body: '' });
const editForm = useForm({ body: '' });
const editingId = ref<number | null>(null);

/** Teinte du rôle : avatar et étiquette (couleur et texte, jamais la couleur seule). */
const roleStyles: Record<string, string> = {
    Coach: 'bg-info/15 text-info',
    Formateur: 'bg-success/20 text-success-foreground dark:text-success',
    Apprenti: 'bg-warning/15 text-warning',
};

const roleStyle = (role: string) =>
    roleStyles[role] ?? 'bg-muted text-muted-foreground';

const page = usePage();
const viewerName = computed(() => page.props.auth?.user?.name ?? '');

const breadcrumbs = computed(() =>
    props.apprenticeId
        ? [
              { label: 'Apprentis', href: apprentisdashboard() },
              {
                  label: props.apprentice?.name ?? 'Apprenti·e',
                  href: apprentices.show(props.apprenticeId),
              },
          ]
        : [{ label: 'Carnet de notes', href: grades.dashboard() }],
);

/** Domaine › sous-groupe de la note, ou sa matière à défaut. */
const gradePath = computed(() =>
    props.grade.path.length > 0
        ? props.grade.path.join(' › ')
        : props.grade.subject,
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
            :description="gradePath"
            :breadcrumbs="breadcrumbs"
        />

        <Card>
            <CardContent class="grid grid-cols-2 gap-6 sm:grid-cols-3">
                <StatItem label="Note" class="col-span-2 sm:col-span-1">
                    <div class="flex items-center gap-3">
                        <p
                            class="text-3xl font-semibold tabular-nums"
                            :class="{
                                'text-destructive': grade.value < PASSING_GRADE,
                            }"
                        >
                            {{ grade.value.toFixed(1) }}
                        </p>
                        <StatusChip :value="grade.value" />
                    </div>
                </StatItem>
                <StatItem label="Date du test">
                    <span class="tabular-nums">{{ grade.date }}</span>
                </StatItem>
                <StatItem label="Semestre">
                    Semestre {{ grade.semester }}
                </StatItem>
            </CardContent>
        </Card>

        <Card class="gap-0 overflow-hidden py-0">
            <div
                class="flex items-center justify-between gap-4 border-b px-6 py-3"
            >
                <h2 class="font-semibold">Justificatif</h2>
                <!-- Les navigateurs mobiles affichent mal un PDF intégré. -->
                <Button v-if="pdfUrl" as-child variant="outline" size="sm">
                    <a :href="pdfUrl" target="_blank" rel="noopener noreferrer">
                        <ExternalLinkIcon aria-hidden="true" />
                        Ouvrir le PDF
                    </a>
                </Button>
            </div>
            <div
                v-if="!pdfUrl"
                class="text-muted-foreground flex flex-col items-center gap-2 px-6 py-12 text-sm"
            >
                <FileXIcon class="size-6" aria-hidden="true" />
                Aucun justificatif déposé pour cette note.
            </div>
            <div v-else class="bg-muted h-[70vh]">
                <embed :src="pdfUrl" type="application/pdf" class="size-full" />
            </div>
        </Card>

        <section class="flex flex-col gap-4">
            <SectionHeader
                title="Commentaires"
                :description="
                    comments.length
                        ? `${comments.length} commentaire${comments.length > 1 ? 's' : ''}`
                        : undefined
                "
            />

            <!--
                Fil de discussion, d'après le Message de reui.io
                (https://reui.io/components/message) : avatar, en-tête,
                bulle, pied. Vos propres commentaires passent à droite.
            -->
            <ol v-if="comments.length" class="flex flex-col gap-5">
                <li
                    v-for="comment in comments"
                    :key="comment.id"
                    :class="
                        cn(
                            'flex items-start gap-3',
                            comment.mine && 'flex-row-reverse',
                        )
                    "
                >
                    <Avatar class="mt-0.5 size-9">
                        <AvatarFallback
                            :class="roleStyle(comment.role)"
                            class="text-xs font-semibold"
                        >
                            {{ getInitials(comment.author) }}
                        </AvatarFallback>
                    </Avatar>

                    <div
                        :class="
                            cn(
                                'flex max-w-[85%] min-w-0 flex-col gap-1.5 sm:max-w-[75%]',
                                comment.mine && 'items-end',
                                editingId === comment.id && 'w-full',
                            )
                        "
                    >
                        <p
                            :class="
                                cn(
                                    'flex flex-wrap items-center gap-x-2 gap-y-1 text-xs',
                                    comment.mine && 'flex-row-reverse',
                                )
                            "
                        >
                            <span class="text-foreground text-sm font-medium">
                                {{ comment.mine ? 'Vous' : comment.author }}
                            </span>
                            <span
                                :class="roleStyle(comment.role)"
                                class="rounded-md px-1.5 py-0.5 font-medium"
                            >
                                {{ comment.role }}
                            </span>
                            <time class="text-muted-foreground tabular-nums">
                                {{ comment.date }}
                            </time>
                        </p>

                        <div
                            v-if="editingId === comment.id"
                            class="flex w-full flex-col gap-2"
                        >
                            <Textarea
                                v-model="editForm.body"
                                maxlength="2000"
                                class="min-h-20 resize-none"
                                @keydown.meta.enter="submitEdit(comment)"
                                @keydown.ctrl.enter="submitEdit(comment)"
                                @keydown.enter.exact.prevent="
                                    submitEdit(comment)
                                "
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
                                        !editForm.body.trim() ||
                                        editForm.processing
                                    "
                                    @click="submitEdit(comment)"
                                >
                                    Enregistrer
                                </Button>
                            </div>
                        </div>

                        <template v-else>
                            <p
                                :class="
                                    cn(
                                        'rounded-2xl px-4 py-2.5 text-sm/relaxed break-words whitespace-pre-line',
                                        comment.mine
                                            ? 'bg-primary text-primary-foreground rounded-tr-sm'
                                            : 'bg-muted rounded-tl-sm',
                                    )
                                "
                            >
                                {{ comment.text }}
                            </p>

                            <div
                                v-if="comment.edited || comment.can.update"
                                class="text-muted-foreground flex items-center gap-3 px-1 text-xs"
                            >
                                <span v-if="comment.edited">Modifié</span>
                                <button
                                    v-if="comment.can.update"
                                    type="button"
                                    class="hover:text-foreground focus-visible:ring-ring/50 flex items-center gap-1 rounded-sm font-medium transition-colors focus-visible:ring-[3px] focus-visible:outline-none"
                                    @click="startEdit(comment)"
                                >
                                    <PencilIcon
                                        class="size-3"
                                        aria-hidden="true"
                                    />
                                    Modifier
                                </button>
                            </div>
                        </template>
                    </div>
                </li>
            </ol>
            <div
                v-else
                class="text-muted-foreground flex flex-col items-center gap-2 rounded-xl border border-dashed px-6 py-8 text-center text-sm"
            >
                <MessageSquareIcon class="size-5" aria-hidden="true" />
                <p>Aucun commentaire pour le moment.</p>
                <p v-if="!can.comment" class="text-xs">
                    Vos coachs et formateurs peuvent commenter cette note.
                </p>
            </div>

            <!-- Zone de saisie : un seul cadre, la barre d'actions en pied. -->
            <div
                v-if="can.comment"
                class="bg-card focus-within:border-ring focus-within:ring-ring/50 flex flex-col rounded-xl border shadow-xs transition-shadow focus-within:ring-[3px]"
            >
                <div class="flex items-start gap-3 p-3">
                    <Avatar class="size-8">
                        <AvatarFallback class="text-xs font-semibold">
                            {{ getInitials(viewerName) }}
                        </AvatarFallback>
                    </Avatar>
                    <Textarea
                        v-model="form.body"
                        placeholder="Écrire un commentaire…"
                        aria-label="Nouveau commentaire"
                        maxlength="2000"
                        class="min-h-16 flex-1 resize-none border-0 p-1 shadow-none focus-visible:ring-0 dark:bg-transparent"
                        @keydown.meta.enter="submitComment"
                        @keydown.ctrl.enter="submitComment"
                        @keydown.enter.exact.prevent="submitComment"
                    />
                </div>
                <FieldError :errors="[form.errors.body]" class="px-4" />
                <div
                    class="bg-muted/40 flex items-center justify-between gap-3 rounded-b-xl border-t px-4 py-2"
                >
                    <p class="text-muted-foreground hidden text-xs sm:block">
                        <kbd class="font-sans font-medium">Entrée</kbd> pour
                        publier ·
                        <kbd class="font-sans font-medium">Maj + Entrée</kbd>
                        pour aller à la ligne
                    </p>
                    <Button
                        size="sm"
                        class="ml-auto"
                        :disabled="!form.body.trim() || form.processing"
                        @click="submitComment"
                    >
                        Publier
                        <SendHorizontalIcon aria-hidden="true" />
                    </Button>
                </div>
            </div>
        </section>
    </PageContainer>
</template>
