import type { IncomingMessage, ServerResponse } from 'http';
import type { ResolvedFontFamily } from './types.js';
export declare function buildDevUrlMap(families: ResolvedFontFamily[], devServerUrl: string): Map<string, string>;
export declare function createFontMiddleware(): {
    middleware: (req: IncomingMessage, res: ServerResponse, next: () => void) => void;
    update: (families: ResolvedFontFamily[]) => void;
};
