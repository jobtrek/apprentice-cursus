import modulesData from '@/data/modules.json';
import MONTHS from '@/data/months.json';
import mpSubjects from '@/data/mp.json';
import normalSubjects from '@/data/normal.json';
import { computed, ref } from 'vue';
import { GRADE_MAX, GRADE_MIN, GRADE_STEP } from '@/constants/constants';

export { MONTHS };

export type GradeMode = 'notes' | 'modules-cie' | 'modules-epsic';

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
    const subjects = computed(() => normalSubjects.filter((subject) => subject.active));
    const MatureSubjects = computed(() => mpSubjects.filter((subject) => subject.active));

    const isMp = ref(false);
    const isEpsic = ref(false);
    const isModuleTest = ref(false);

    const cieModules = computed(() => modulesData.filter((module) => module.school === 'CIE'));
    const epsicModules = computed(() => modulesData.filter((module) => module.school === 'EPSIC'));

    const isOral = ref(false);
    const grade = ref<number | string>(4.5);

    const { dateDay, dateMonth, dateYear, isoDate: testDate } = useDateParts();
    const { selectedFile, fileInput, handleDrop, onFileChange } = useFileInput();

    const selectedSubject = ref<Subject | undefined>();
    const selectedModule = ref<ModuleEntry | undefined>();


    // complicated shit since js can't do basic math.
    const clampGrade = (value: number) =>
        Math.min(GRADE_MAX, Math.max(GRADE_MIN, Math.round(value * 10) / 10));

    const stepGrade = (direction: 1 | -1) => {
        const current = Number.isNaN(Number(grade.value)) ? GRADE_MIN : Number(grade.value);
        grade.value = clampGrade(current + direction * GRADE_STEP);
    };

    const decrementGrade = () => stepGrade(-1);
    const incrementGrade = () => stepGrade(1);

    const switchToOral = () => {
        isOral.value = !isOral.value;
        if (isOral.value) selectedFile.value = null;
    };

    // Derives/updates isModuleTest + isEpsic from a single 3-way mode
    const gradeMode = computed<GradeMode>({
        get() {
            if (!isModuleTest.value) return 'notes';
            return isEpsic.value ? 'modules-epsic' : 'modules-cie';
        },
        set(value) {
            if (!value) return;
            isModuleTest.value = value !== 'notes';
            isEpsic.value = value === 'modules-epsic';
        },
    });

    return {
        subjects,
        MatureSubjects,
        isMp,
        isEpsic,
        isModuleTest,
        cieModules,
        epsicModules,
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
    };
};