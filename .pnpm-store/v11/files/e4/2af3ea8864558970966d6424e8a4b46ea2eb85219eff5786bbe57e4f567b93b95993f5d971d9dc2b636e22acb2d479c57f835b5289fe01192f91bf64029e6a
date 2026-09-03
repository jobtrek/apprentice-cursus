export interface SearchIndex {
    count: number;
    lineOf: number[];
    firstMatchOnLine: Map<number, number>;
}
export declare function stripAnsi(raw: string): string;
export declare function indexMatches(lines: string[], query: string): SearchIndex;
export declare function highlightLine(raw: string, query: string, activeMatchIdx: number, firstMatchIdx: number): string;
