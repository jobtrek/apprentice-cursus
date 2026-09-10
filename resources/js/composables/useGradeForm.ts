import modulesData from '@/data/modules.json';
import mpSubjects from '@/data/mp.json';
import normalSubjects from '@/data/normal.json';
import { computed, ref } from 'vue';
import { ALLOWED_FILE_MIME_TYPE, GRADE_MAX, GRADE_MIN, GRADE_STEP, MAX_FILE_SIZE_BYTES } from '@/constants/constants';

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
    const selectedSubject = ref('');
    const selectedModule = ref('');

    const selectedFile = ref<File | null>(null);
    const fileInput = ref<HTMLInputElement | null>(null);
    const fileError = ref('');

    const validateFile = (file: File): boolean => {
        if (file.type !== ALLOWED_FILE_MIME_TYPE) {
            fileError.value = 'Le fichier doit être un PDF.';
            return false;
        }
        if (file.size > MAX_FILE_SIZE_BYTES) {
            fileError.value = 'Le fichier ne doit pas dépasser 10 Mo.';
            return false;
        }
        fileError.value = '';
        return true;
    };

    const decrementGrade = () => {
        grade.value = Math.max(GRADE_MIN, Math.round((grade.value - GRADE_STEP) * 10) / 10);
    };
    const incrementGrade = () => {
        grade.value = Math.min(GRADE_MAX, Math.round((grade.value + GRADE_STEP) * 10) / 10);
    };

    const handleDrop = (event: DragEvent) => {
        event.preventDefault();
        const file = event.dataTransfer?.files?.[0];
        if (file && validateFile(file)) {
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
        const file = target.files?.[0] ?? null;
        selectedFile.value = file && validateFile(file) ? file : null;
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
        selectedSubject,
        selectedModule,
        selectedFile,
        fileInput,
        fileError,
        decrementGrade,
        incrementGrade,
        handleDrop,
        switchToOral,
        onFileChange,
    };
}
