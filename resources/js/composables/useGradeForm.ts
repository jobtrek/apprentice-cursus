import modulesData from '@/data/modules.json';
import MONTHS from '@/data/months.json';
import mpSubjects from '@/data/mp.json';
import normalSubjects from '@/data/normal.json';
import { computed, ref } from 'vue';
import { GRADE_MAX, GRADE_MIN, GRADE_STEP } from '@/constants/constants';

export { MONTHS };

export function useGradeForm() {
    const subjects = computed(() => normalSubjects.filter((subject) => subject.active));
    const MatureSubjects = computed(() => mpSubjects.filter((subject) => subject.active));

    const isMp = ref(false);
    const isEpsic = ref(false);
    const isModuleTest = ref(false);

    const cieModules = computed(() => modulesData.filter((module) => module.school === 'CIE'));
    const epsicModules = computed(() => modulesData.filter((module) => module.school === 'EPSIC'));

    const isOral = ref(false);
    const grade = ref<number | string>(4.5);

    const dateDay = ref('');
    const dateMonth = ref('');
    const dateYear = ref('');

    const testDate = computed(() => {
        if (!dateDay.value || !dateMonth.value || !dateYear.value) {
            return '';
        }
        const day = dateDay.value.padStart(2, '0');
        const month = dateMonth.value.padStart(2, '0');
        return `${dateYear.value}-${month}-${day}`;
    });

    const selectedSubject = ref<{ name: string; active: boolean } | undefined>();
    const selectedModule = ref<{ id: number; code: number; school: string; name: string } | undefined>();

    const selectedFile = ref<File | null>(null);
    const fileInput = ref<HTMLInputElement | null>(null);

    const decrementGrade = () => {
        const current = Number.isNaN(Number(grade.value)) ? GRADE_MIN : Number(grade.value);
        grade.value = Math.max(GRADE_MIN, Math.round((current - GRADE_STEP) * 10) / 10);
    };
    const incrementGrade = () => {
        const current = Number.isNaN(Number(grade.value)) ? GRADE_MIN : Number(grade.value);
        grade.value = Math.min(GRADE_MAX, Math.round((current + GRADE_STEP) * 10) / 10);
    };

    const handleDrop = (event: DragEvent) => {
        event.preventDefault();
        const file = event.dataTransfer?.files?.[0];
        if (file) {
            selectedFile.value = file;
        }
    };

    const switchToOral = () => {
        isOral.value = !isOral.value;
        if (isOral.value) {
            selectedFile.value = null;
        }
    };

    const onFileChange = (event: Event) => {
        const target = event.target as HTMLInputElement;
        selectedFile.value = target.files?.[0] ?? null;
    };

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
    };
}
