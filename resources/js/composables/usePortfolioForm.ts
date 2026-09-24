import { useForm } from '@inertiajs/vue3';
import { computed, onScopeDispose, ref } from 'vue';
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
    /** Nouveaux fichiers seulement ; les captures enregistrées sont gardées par id. */
    screenshots: File[];
    kept_screenshot_ids: number[];
    skill_ids: number[];
};

/** Vignette affichée dans le formulaire, enregistrée ou pas encore envoyée. */
export type ScreenshotPreview =
    | { kind: 'saved'; id: number; url: string }
    | { kind: 'new'; file: File; url: string };

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
        technologies: [...(project?.technologies ?? [])],
        repository_url: project?.repository_url ?? '',
        demo_path: project?.demo_path ?? '',
        date_start: project?.date_start ?? '',
        date_end: project?.date_end ?? '',
        screenshots: [],
        kept_screenshot_ids: (project?.screenshots ?? []).map(({ id }) => id),
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

    /** URL locales des nouveaux fichiers, libérées quand on les retire. */
    const objectUrls = new Map<File, string>();

    const screenshotPreviews = computed<ScreenshotPreview[]>(() => [
        ...(existing?.screenshots ?? [])
            .filter(({ id }) => form.kept_screenshot_ids.includes(id))
            .map(({ id, url }) => ({ kind: 'saved' as const, id, url })),
        ...form.screenshots.map((file) => ({
            kind: 'new' as const,
            file,
            url: objectUrls.get(file) ?? '',
        })),
    ]);

    function addScreenshots(files: FileList | null): void {
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

            objectUrls.set(file, URL.createObjectURL(file));
            form.screenshots.push(file);
        }

        screenshotError.value = rejected.length > 0 ? rejected.join(' ') : null;
    }

    function removeScreenshot(preview: ScreenshotPreview): void {
        if (preview.kind === 'saved') {
            form.kept_screenshot_ids = form.kept_screenshot_ids.filter(
                (id) => id !== preview.id,
            );

            return;
        }

        URL.revokeObjectURL(preview.url);
        objectUrls.delete(preview.file);
        form.screenshots = form.screenshots.filter(
            (file) => file !== preview.file,
        );
    }

    onScopeDispose(() => {
        objectUrls.forEach((url) => URL.revokeObjectURL(url));
    });

    /**
     * Toujours en multipart pour envoyer les fichiers. Un PUT multipart n'est pas
     * lu par PHP : la mise à jour passe par POST avec `_method` (method spoofing).
     */
    function submit(): void {
        if (existing) {
            form.transform((data) => ({ ...data, _method: 'put' })).post(
                portfolio.projects.update.url(existing.id),
                { forceFormData: true },
            );

            return;
        }

        form.post(portfolio.projects.store.url(), { forceFormData: true });
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
        screenshotPreviews,
        addScreenshots,
        removeScreenshot,
        submit,
    };
}
