export type ScanReading = {
    scan_id: number;
    subject: string;
    confidence: number | null;
    grade: number | null;
    flagged: boolean;
    duration_ms: number;
};

export type ScanResult = {
    file: File;
    scanId: number;
    branchId: number | null;
    grade: number | null;
};

export type ScanStatus =
    | 'incomplete'
    | 'ready'
    | 'analysing'
    | 'needsEdit'
    | 'approvable';
