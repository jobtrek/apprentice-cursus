export type ScanReading = {
    scan_id: number;
    subject: string;
    confidence: number | null;
    grade: number | null;
    flagged: boolean;
    duration_ms: number;
};
