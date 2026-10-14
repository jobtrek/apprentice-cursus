import MONTHS from '@/data/months.json';
import { useForm } from '@inertiajs/vue3';
import { Temporal } from 'temporal-polyfill';
import { computed, ref } from 'vue';
import {
    ALLOWED_FILE_MIME_TYPE,
    GRADE_MAX,
    GRADE_MIN,
    GRADE_STEP,
    MAX_FILE_SIZE_BYTES,
} from '@/constants/constants';
import type { GradeTree } from '@/lib/gradebook';
import grades from '@/routes/grades';
import type { Grade } from '@/types/grade';

export { MONTHS };

/** Feuille de l'arbre : la seule sorte de nœud qui porte des notes. */
export type Branch = { id: number; name: string };

export type BranchGroup = { name: string; branches: Branch[] };

/** Note modifiée, et si elle a déjà un scan (sinon : épreuve orale). */
export type EditedGrade = { grade: Grade; hasFile: boolean };

type GradeFormData = {
    average_node_id: number | null;
    value: number | string;
    test_date: string;
    is_oral: boolean;
    file: File | null;
};

/**
 * Les feuilles de l'arbre de la filière, groupées par parent direct
 * (« Modules école professionnelle », « Culture générale »…), dans l'ordre
 * de l'arbre.
 */
const branchGroups = (tree: GradeTree | null): BranchGroup[] => {
    if (!tree) return [];

    const groups: BranchGroup[] = [];

    const walk = (id: number) => {
        const node = tree.nodes[id];
        if (!node) return;

        const leaves = node.children
            .map((child) => tree.nodes[child.id])
            .filter((child) => child && !child.aggregated)
            .map((child) => ({ id: child.id, name: child.name }));

        if (leaves.length) groups.push({ name: node.name, branches: leaves });

        node.children.forEach((child) => walk(child.id));
    };

    walk(tree.root);

    return groups;
};

/**
 * Pré-remplie avec la date du jour : une note se saisit souvent le jour du test.
 * En modification, part de la date de la note (`dd.mm.yyyy`).
 */
const useDateParts = (initial?: string) => {
    const today = Temporal.Now.plainDateISO();
    const [day, month, year] = initial
        ? initial.split('.').map(Number)
        : [today.day, today.month, today.year];
    // Mêmes formats que les champs : mois sans zéro, comme dans months.json.
    const dateDay = ref(String(day));
    const dateMonth = ref(String(month));
    const dateYear = ref(String(year));

    /** Vide si la date est incomplète ou n'existe pas (31 février…). */
    const isoDate = computed(() => {
        try {
            return Temporal.PlainDate.from(
                {
                    year: Number(dateYear.value),
                    month: Number(dateMonth.value),
                    day: Number(dateDay.value),
                },
                { overflow: 'reject' },
            ).toString();
        } catch {
            return '';
        }
    });

    const isFuture = computed(
        () =>
            isoDate.value !== '' &&
            Temporal.PlainDate.compare(isoDate.value, today) > 0,
    );

    return { dateDay, dateMonth, dateYear, isoDate, isFuture };
};

export const useGradeForm = (
    tree: () => GradeTree | null,
    edited?: EditedGrade,
) => {
    const groups = computed(() => branchGroups(tree()));
    const branches = computed(() =>
        groups.value.flatMap((group) => group.branches),
    );

    const form = useForm<GradeFormData>({
        average_node_id: edited?.grade.node_id ?? null,
        value: edited?.grade.value ?? 4.5,
        test_date: '',
        is_oral: edited ? !edited.hasFile : false,
        file: null,
    });

    /** En modification, le scan déjà déposé est gardé si aucun autre ne le remplace. */
    const keepsFile = computed(
        () => !!edited?.hasFile && !form.is_oral && !form.file,
    );

    const selectedBranch = computed<Branch | undefined>({
        get: () =>
            branches.value.find((branch) => branch.id === form.average_node_id),
        set: (branch) => (form.average_node_id = branch?.id ?? null),
    });

    const { dateDay, dateMonth, dateYear, isoDate, isFuture } = useDateParts(
        edited?.grade.date,
    );

    const fileInput = ref<HTMLInputElement | null>(null);

    const handleDrop = (event: DragEvent) => {
        event.preventDefault();
        const file = event.dataTransfer?.files?.[0];
        if (file) form.file = file;
    };

    const onFileChange = (event: Event) => {
        const target = event.target as HTMLInputElement;
        form.file = target.files?.[0] ?? null;
    };

    // complicated shit since js can't do basic math.
    const clampGrade = (value: number) =>
        Math.min(GRADE_MAX, Math.max(GRADE_MIN, Math.round(value * 10) / 10));

    const stepGrade = (direction: 1 | -1) => {
        const current = Number.isNaN(Number(form.value))
            ? GRADE_MIN
            : Number(form.value);
        form.value = clampGrade(current + direction * GRADE_STEP);
    };

    const decrementGrade = () => stepGrade(-1);
    const incrementGrade = () => stepGrade(1);

    const switchToOral = () => {
        form.is_oral = !form.is_oral;
        if (form.is_oral) form.file = null;
    };

    /**
     * Mêmes règles que `GradeRequest`, pour répondre sans aller-retour ;
     * le serveur reste juge. Les messages vont dans `form.errors`, par champ.
     */
    const validate = (): boolean => {
        form.clearErrors();

        if (!form.average_node_id) {
            form.setError('average_node_id', 'Sélectionnez une branche.');
        }

        const value = Number(form.value);
        if (
            Number.isNaN(value) ||
            value < GRADE_MIN ||
            value > GRADE_MAX ||
            value % GRADE_STEP !== 0
        ) {
            form.setError(
                'value',
                `La note doit être comprise entre ${GRADE_MIN.toFixed(1)} et ${GRADE_MAX.toFixed(1)}, par pas de ${GRADE_STEP.toFixed(1)}.`,
            );
        }

        if (!isoDate.value) {
            form.setError(
                'test_date',
                'Indiquez un jour, un mois et une année qui existent.',
            );
        } else if (isFuture.value) {
            form.setError(
                'test_date',
                'La date du test ne peut pas être dans le futur.',
            );
        }

        if (!form.is_oral && !keepsFile.value) {
            const file = form.file;

            if (!file) {
                form.setError(
                    'file',
                    'Ajoutez le scan du test, ou indiquez une épreuve orale.',
                );
            } else if (file.type !== ALLOWED_FILE_MIME_TYPE) {
                form.setError(
                    'file',
                    'Le justificatif doit être un fichier PDF.',
                );
            } else if (file.size > MAX_FILE_SIZE_BYTES) {
                form.setError(
                    'file',
                    'Le justificatif ne doit pas dépasser 10 Mo.',
                );
            }
        }

        return !form.hasErrors;
    };

    const submit = (onSuccess: () => void): void => {
        if (!validate()) return;

        form.transform((data) => ({
            ...data,
            test_date: isoDate.value,
        })).submit(
            edited
                ? // PHP ne lit pas un PUT multipart : POST avec `_method=PUT`.
                  {
                      url: grades.update.form(edited.grade.id).action,
                      method: 'post',
                  }
                : grades.store(),
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess,
            },
        );
    };

    return {
        form,
        groups,
        selectedBranch,
        keepsFile,
        dateDay,
        dateMonth,
        dateYear,
        fileInput,
        decrementGrade,
        incrementGrade,
        handleDrop,
        switchToOral,
        onFileChange,
        submit,
    };
};
