import modulesData from '@/data/modules.json';
import MONTHS from '@/data/months.json';
import mpSubjects from '@/data/mp.json';
import normalSubjects from '@/data/normal.json';
import { computed, ref } from 'vue';
import {
    ALLOWED_FILE_MIME_TYPE,
    GRADE_MAX,
    GRADE_MIN,
    GRADE_STEP,
    MAX_FILE_SIZE_BYTES,
} from '@/constants/constants';

export { MONTHS };

export type GradeMode = 'notes' | 'modules';

type Subject = (typeof normalSubjects)[number];
type ModuleEntry = (typeof modulesData)[number];

const useDateParts = () => {
    const dateDay = ref('');
    const dateMonth = ref('');
    const dateYear = ref('');

    const isoDate = computed(() => {
        if (!dateDay.value || !dateMonth.value || !dateYear.value) return '';
        return `${dateYear.value}-${dateMonth.value.padStart(2, '0')}-${dateDay.value.padStart(2, '0')}`;
    });

    return { dateDay, dateMonth, dateYear, isoDate };
};

const useFileInput = () => {
    const selectedFile = ref<File | null>(null);
    const fileInput = ref<HTMLInputElement | null>(null);

    const handleDrop = (event: DragEvent) => {
        event.preventDefault();
        const file = event.dataTransfer?.files?.[0];
        if (file) selectedFile.value = file;
    };

    const onFileChange = (event: Event) => {
        const target = event.target as HTMLInputElement;
        selectedFile.value = target.files?.[0] ?? null;
    };

    return { selectedFile, fileInput, handleDrop, onFileChange };
};

export const useGradeForm = () => {
    const subjects = computed(() =>
        normalSubjects.filter((subject) => subject.active),
    );
    const MatureSubjects = computed(() =>
        mpSubjects.filter((subject) => subject.active),
    );

    const isMp = ref(false);
    const isModuleTest = ref(false);

    const modules = computed(() => modulesData);

    const isOral = ref(false);
    const grade = ref<number | string>(4.5);

    const { dateDay, dateMonth, dateYear, isoDate: testDate } = useDateParts();
    const { selectedFile, fileInput, handleDrop, onFileChange } =
        useFileInput();

    const selectedSubject = ref<Subject | undefined>();
    const selectedModule = ref<ModuleEntry | undefined>();

    // complicated shit since js can't do basic math.
    const clampGrade = (value: number) =>
        Math.min(GRADE_MAX, Math.max(GRADE_MIN, Math.round(value * 10) / 10));

    const stepGrade = (direction: 1 | -1) => {
        const current = Number.isNaN(Number(grade.value))
            ? GRADE_MIN
            : Number(grade.value);
        grade.value = clampGrade(current + direction * GRADE_STEP);
    };

    const decrementGrade = () => stepGrade(-1);
    const incrementGrade = () => stepGrade(1);

    const switchToOral = () => {
        isOral.value = !isOral.value;
        if (isOral.value) selectedFile.value = null;
    };

    const errors = ref<Record<string, string>>({});

    /**
     * Valide la saisie et renvoie `true` si la note peut être enregistrée.
     * Les messages sont exposés dans `errors`, indexés par champ.
     */
    const validate = (): boolean => {
        const found: Record<string, string> = {};

        if (isModuleTest.value && !selectedModule.value) {
            found.module = 'Sélectionnez un module.';
        }

        if (!isModuleTest.value && !selectedSubject.value) {
            found.subject = 'Sélectionnez une matière.';
        }

        const value = Number(grade.value);
        if (Number.isNaN(value) || value < GRADE_MIN || value > GRADE_MAX) {
            found.grade = `La note doit être comprise entre ${GRADE_MIN.toFixed(1)} et ${GRADE_MAX.toFixed(1)}.`;
        }

        if (!testDate.value) {
            found.date = "Indiquez le jour, le mois et l'année du test.";
        }

        if (!isModuleTest.value && !isOral.value) {
            const file = selectedFile.value;

            if (!file) {
                found.file =
                    'Ajoutez le scan du test, ou indiquez une épreuve orale.';
            } else if (file.type !== ALLOWED_FILE_MIME_TYPE) {
                found.file = 'Le justificatif doit être un fichier PDF.';
            } else if (file.size > MAX_FILE_SIZE_BYTES) {
                found.file = 'Le justificatif ne doit pas dépasser 10 Mo.';
            }
        }

        errors.value = found;

        return Object.keys(found).length === 0;
    };

    // Derives/updates isModuleTest from a single 2-way mode
    const gradeMode = computed<GradeMode>({
        get() {
            return isModuleTest.value ? 'modules' : 'notes';
        },
        set(value) {
            if (!value) return;
            isModuleTest.value = value === 'modules';
        },
    });

    return {
        subjects,
        MatureSubjects,
        isMp,
        isModuleTest,
        modules,
        isOral,
        grade,
        dateDay,
        dateMonth,
        dateYear,
        testDate,
        selectedSubject,
        selectedModule,
        selectedFile,
        fileInput,
        decrementGrade,
        incrementGrade,
        handleDrop,
        switchToOral,
        onFileChange,
        gradeMode,
        errors,
        validate,
    };
};
