import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { parseTechnologies } from '@/composables/usePortfolio';
import portfolio from '@/routes/portfolio';
import type { PortfolioProject } from '@/types/portfolio';

type ProjectForm = {
    title: string;
    organization: string;
    description: string;
    responsibilities: string;
    technologies: string[];
    repository_url: string;
    demo_path: string;
    date_start: string;
    date_end: string;
    screenshots: string[];
    skill_ids: number[];
};

const MAX_SCREENSHOT_BYTES = 5 * 1024 * 1024;

/**
 * Les champs vides partent en chaîne vide : le middleware
 * ConvertEmptyStringsToNull de Laravel les transforme en null.
 */
function toForm(project?: PortfolioProject): ProjectForm {
    return {
        title: project?.title ?? '',
        organization: project?.organization ?? '',
        description: project?.description ?? '',
        responsibilities: project?.responsibilities ?? '',
        technologies: parseTechnologies(project?.technologies ?? null),
        repository_url: project?.repository_url ?? '',
        demo_path: project?.demo_path ?? '',
        date_start: project?.date_start ?? '',
        date_end: project?.date_end ?? '',
        screenshots: [...(project?.screenshots ?? [])],
        skill_ids: [...(project?.skill_ids ?? [])],
    };
}

export function usePortfolioForm(existing?: PortfolioProject) {
    const form = useForm<ProjectForm>(toForm(existing));
    const technologyDraft = ref('');
    const screenshotError = ref<string | null>(null);

    const isEditing = computed(() => existing !== undefined);

    /**
     * Erreurs de la Form Request, plus le rejet local des captures. Celles des
     * éléments de liste (`skill_ids.0`) sont regroupées sous leur champ.
     */
    const errors = computed<Partial<Record<keyof ProjectForm, string>>>(() => {
        const found: Partial<Record<keyof ProjectForm, string>> = {};

        for (const [key, message] of Object.entries(form.errors)) {
            const field = key.split('.')[0] as keyof ProjectForm;
            found[field] ??= message;
        }

        if (screenshotError.value) {
            found.screenshots = screenshotError.value;
        }

        return found;
    });

    function addTechnology(): void {
        const technology = technologyDraft.value.trim();

        if (technology.length === 0 || form.technologies.includes(technology)) {
            technologyDraft.value = '';

            return;
        }

        form.technologies.push(technology);
        technologyDraft.value = '';
    }

    function removeTechnology(technology: string): void {
        form.technologies = form.technologies.filter(
            (candidate) => candidate !== technology,
        );
    }

    /** Retire la dernière techno quand on recule dans un champ déjà vide. */
    function removeLastTechnology(): void {
        if (technologyDraft.value.length === 0) {
            form.technologies.pop();
        }
    }

    function toggleSkill(id: number): void {
        const index = form.skill_ids.indexOf(id);

        if (index === -1) {
            form.skill_ids.push(id);

            return;
        }

        form.skill_ids.splice(index, 1);
    }

    function hasSkill(id: number): boolean {
        return form.skill_ids.includes(id);
    }

    /**
     * Les images sont lues en data URL pour l'aperçu uniquement : elles ne
     * sont pas envoyées tant que le serveur n'a pas de stockage pour elles.
     */
    function readScreenshot(file: File): Promise<string> {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => {
                // readAsDataURL donne toujours une chaîne, mais le type de
                // FileReader couvre aussi ArrayBuffer.
                if (typeof reader.result === 'string') {
                    resolve(reader.result);

                    return;
                }

                reject(new Error('Lecture du fichier impossible.'));
            };
            reader.onerror = () => reject(reader.error);
            reader.readAsDataURL(file);
        });
    }

    async function addScreenshots(files: FileList | null): Promise<void> {
        if (!files || files.length === 0) {
            return;
        }

        const rejected: string[] = [];

        for (const file of Array.from(files)) {
            if (!file.type.startsWith('image/')) {
                rejected.push(`${file.name} n'est pas une image.`);

                continue;
            }

            if (file.size > MAX_SCREENSHOT_BYTES) {
                rejected.push(`${file.name} dépasse 5 Mo.`);

                continue;
            }

            form.screenshots.push(await readScreenshot(file));
        }

        screenshotError.value = rejected.length > 0 ? rejected.join(' ') : null;
    }

    function removeScreenshot(index: number): void {
        form.screenshots.splice(index, 1);
    }

    function submit(): void {
        // Pas encore de stockage serveur pour les captures (voir readScreenshot).
        const payload = form.transform(
            ({ screenshots: _screenshots, ...data }) => data,
        );

        if (existing) {
            payload.put(portfolio.projects.update.url(existing.id));

            return;
        }

        payload.post(portfolio.projects.store.url());
    }

    return {
        form,
        errors,
        isEditing,
        technologyDraft,
        addTechnology,
        removeTechnology,
        removeLastTechnology,
        toggleSkill,
        hasSkill,
        addScreenshots,
        removeScreenshot,
        submit,
    };
}
