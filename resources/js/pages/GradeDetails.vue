<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import {
    PageContainer,
    PageHeader,
    SectionHeader,
    StatItem,
} from '@/components/page';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { findApprentice } from '@/composables/useApprentices';
import { useNavigation } from '@/composables/useNavigation';
import { apprentisdashboard } from '@/routes';
import apprentices from '@/routes/apprentices';
import grades from '@/routes/grades';
import { findGrade } from '@/data/gradebook';
import type { UserRole } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { FileXIcon } from '@lucide/vue';
import { computed, ref } from 'vue';

type Comment = {
    author: string;
    role: string;
    date: string;
    text: string;
};

const props = defineProps<{
    gradeId: number;
    pdfUrl?: string;
    comments?: Comment[];
    /** Présent quand un coach ou formateur consulte la note d'un·e apprenti·e. */
    apprenticeId?: number;
}>();

const grade = computed(() => findGrade(props.gradeId));

const comments = ref<Comment[]>(props.comments ?? []);
const newComment = ref('');

const { role } = useNavigation();

/** Libellé de l'auteur d'un commentaire, selon les rôles autorisés à commenter. */
const COMMENTER_LABELS: Partial<Record<UserRole, string>> = {
    coach: 'Coach',
    trainer: 'Formateur',
};

const commenterLabel = computed(() =>
    role.value ? COMMENTER_LABELS[role.value] : undefined,
);
const canComment = computed(() => commenterLabel.value !== undefined);

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

/** Page à laquelle revenir : le dernier niveau du fil d'Ariane. */
const backHref = computed(() => breadcrumbs.value.at(-1)!.href);

const submitComment = () => {
    if (!newComment.value.trim() || !commenterLabel.value) return;

    comments.value.push({
        author: 'Vous',
        role: commenterLabel.value,
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
    <Head :title="grade?.title ?? 'Note introuvable'" />

    <PageContainer v-if="!grade">
        <PageHeader title="Note introuvable" :breadcrumbs="breadcrumbs" />

        <Empty class="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <FileXIcon />
                </EmptyMedia>
                <EmptyTitle>Aucune note ne correspond</EmptyTitle>
                <EmptyDescription>
                    Cette note n'existe pas ou a été supprimée.
                </EmptyDescription>
            </EmptyHeader>
            <EmptyContent>
                <Button as-child variant="outline">
                    <Link :href="backHref">Retour</Link>
                </Button>
            </EmptyContent>
        </Empty>
    </PageContainer>

    <PageContainer v-else>
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

            <div v-if="canComment" class="flex flex-col gap-2 pt-2">
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
                Seuls les coachs et formateurs peuvent commenter cette
                évaluation.
            </p>
        </section>
    </PageContainer>
</template>
