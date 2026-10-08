<script setup lang="ts">
import { FolderOpenIcon, PencilIcon } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatPeriod } from '@/composables/usePortfolio';
import type { PortfolioProject } from '@/types/portfolio';

/**
 * Projets sur une frise verticale, du plus récent au plus ancien. Adaptée de
 * la Timeline de reui.io (https://reui.io/components/timeline).
 */
const props = defineProps<{
    projects: PortfolioProject[];
    /** Coach et formateur : pas de modification. */
    readonly?: boolean;
}>();

defineEmits<{ edit: [project: PortfolioProject] }>();

/** Dates ISO `YYYY-MM-DD` : l'ordre alphabétique est l'ordre chronologique. */
const sorted = computed(() =>
    [...props.projects].sort((a, b) =>
        b.date_start.localeCompare(a.date_start),
    ),
);
</script>

<template>
    <ol class="flex flex-col" aria-label="Chronologie des projets">
        <li
            v-for="project in sorted"
            :key="project.id"
            class="group/item relative grid gap-x-6 pb-6 last:pb-0 sm:grid-cols-[10rem_1fr]"
        >
            <time
                :datetime="project.date_start"
                class="text-muted-foreground mb-2 pl-8 text-xs font-medium sm:mb-0 sm:pt-1 sm:pl-0 sm:text-right"
            >
                {{ formatPeriod(project.date_start, project.date_end) }}
            </time>

            <div class="relative pl-8">
                <!-- Ligne de la frise, interrompue après le dernier projet. -->
                <span
                    class="bg-border absolute top-5 -bottom-6 left-[7px] w-0.5 group-last/item:hidden"
                    aria-hidden="true"
                />
                <!-- Repère : plein si terminé, pulsé si en cours. -->
                <span
                    class="absolute top-1 left-0 flex size-4 items-center justify-center"
                    aria-hidden="true"
                >
                    <span
                        v-if="!project.date_end"
                        class="bg-success/40 absolute inline-flex size-full animate-ping rounded-full motion-reduce:hidden"
                    />
                    <span
                        class="relative size-4 rounded-full border-2"
                        :class="
                            project.date_end
                                ? 'border-primary bg-primary'
                                : 'border-success bg-background'
                        "
                    />
                </span>

                <article
                    class="bg-card flex gap-4 rounded-xl border p-4 shadow-xs transition-shadow hover:shadow-md"
                >
                    <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span v-if="readonly" class="font-medium">
                                {{ project.title }}
                            </span>
                            <button
                                v-else
                                type="button"
                                class="focus-visible:ring-ring/50 rounded-sm text-left font-medium hover:underline focus-visible:ring-[3px] focus-visible:outline-none"
                                @click="$emit('edit', project)"
                            >
                                {{ project.title }}
                            </button>
                            <Badge
                                v-if="!project.date_end"
                                class="bg-success/15 text-success border-transparent"
                            >
                                En cours
                            </Badge>
                        </div>
                        <p
                            v-if="
                                project.organization || project.responsibilities
                            "
                            class="text-muted-foreground text-sm"
                        >
                            {{
                                [project.organization, project.responsibilities]
                                    .filter(Boolean)
                                    .join(' · ')
                            }}
                        </p>
                        <p class="text-muted-foreground line-clamp-2 text-sm">
                            {{ project.description }}
                        </p>
                        <div
                            v-if="project.technologies.length > 0"
                            class="flex flex-wrap gap-1 pt-1"
                        >
                            <Badge
                                v-for="technology in project.technologies"
                                :key="technology"
                                variant="outline"
                            >
                                {{ technology }}
                            </Badge>
                        </div>
                    </div>

                    <div class="flex flex-none flex-col items-end gap-2">
                        <div
                            class="bg-muted text-muted-foreground hidden h-20 w-32 items-center justify-center overflow-hidden rounded-lg border sm:flex"
                        >
                            <img
                                v-if="project.screenshots.length > 0"
                                :src="project.screenshots[0].url"
                                alt=""
                                class="size-full object-cover"
                            />
                            <FolderOpenIcon
                                v-else
                                class="size-5"
                                aria-hidden="true"
                            />
                        </div>
                        <Button
                            v-if="!readonly"
                            variant="ghost"
                            size="icon-sm"
                            :aria-label="`Modifier ${project.title}`"
                            :title="`Modifier ${project.title}`"
                            @click="$emit('edit', project)"
                        >
                            <PencilIcon aria-hidden="true" />
                        </Button>
                    </div>
                </article>
            </div>
        </li>
    </ol>
</template>
