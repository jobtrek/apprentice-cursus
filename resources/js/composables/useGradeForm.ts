import modulesData from '@/data/modules.json';
import mpSubjects from '@/data/mp.json';
import normalSubjects from '@/data/normal.json';
import { computed, ref } from 'vue';
import { GRADE_MAX, GRADE_MIN, GRADE_STEP } from '@/constants/constants';

export function useGradeForm() {
    const subjects = normalSubjects;
    const MatureSubjects = mpSubjects;

    const is_mp = ref(false);
    const is_epsic = ref(false);
    const is_module_test = ref(false);

    const cieModules = computed(() => modulesData.filter((module) => module.school === 'CIE'));
    const epsicModules = computed(() => modulesData.filter((module) => module.school === 'EPSIC'));

    const is_oral = ref(false);
    const grade = ref(4.5);
    const testDate = ref('');

    const selectedFile = ref<File | null>(null);
    const fileInput = ref<HTMLInputElement | null>(null);

    const decrementGrade = () => {
        grade.value = Math.max(GRADE_MIN, Math.round((grade.value - GRADE_STEP) * 10) / 10);
    };
    const incrementGrade = () => {
        grade.value = Math.min(GRADE_MAX, Math.round((grade.value + GRADE_STEP) * 10) / 10);
    };

    const handleDrop = (event: DragEvent) => {
        event.preventDefault();
        const file = event.dataTransfer?.files?.[0];
        if (file) {
            selectedFile.value = file;
        }
    };

    const switchToOral = () => {
        is_oral.value = !is_oral.value;
        if (is_oral.value) {
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
        is_mp,
        is_epsic,
        is_module_test,
        cieModules,
        epsicModules,
        is_oral,
        grade,
        testDate,
        selectedFile,
        fileInput,
        decrementGrade,
        incrementGrade,
        handleDrop,
        switchToOral,
        onFileChange,
    };
}
