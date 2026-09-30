export const GRADE_MIN = 1.0;
export const GRADE_MAX = 6.0;
export const GRADE_STEP = 0.5;

export const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024;
export const ALLOWED_FILE_MIME_TYPE = 'application/pdf';

export const TRACK_FILTER_OPTIONS = [
    { label: 'Toutes', value: 'All' },
    { label: 'IT', value: 'IT' },
    { label: 'EC', value: 'EC' },
] as const;

export const YEAR_FILTER_OPTIONS = [
    { label: 'Toutes', value: 'All' },
    { label: '1ère', value: '1ère' },
    { label: '2ème', value: '2ème' },
    { label: '3ème', value: '3ème' },
    { label: '4ème', value: '4ème' },
] as const;
