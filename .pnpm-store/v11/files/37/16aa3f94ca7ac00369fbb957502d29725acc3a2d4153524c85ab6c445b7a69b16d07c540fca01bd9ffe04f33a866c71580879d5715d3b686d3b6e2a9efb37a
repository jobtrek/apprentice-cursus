import type { ResolvedFontFamily, FallbackMetrics } from './types.js';
export declare function generateFontFace(family: ResolvedFontFamily, filePathMap: Map<string, string>): string;
export declare function generateFallbackFontFace(fallbackFamily: string, metrics: FallbackMetrics): string;
export declare function generateFontClassForFamily(family: ResolvedFontFamily): string;
export declare function generateFontClasses(families: ResolvedFontFamily[]): string;
export declare function generateCssVariables(families: ResolvedFontFamily[]): string;
export declare function generateCssVariablesMap(families: ResolvedFontFamily[]): Record<string, string>;
export declare function generateFamilyStyles(families: ResolvedFontFamily[], filePathMap: Map<string, string>, fallbackMap?: Map<string, {
    fallbackFamily: string;
    metrics: FallbackMetrics;
}>): {
    familyStyles: Record<string, string>;
    variables: Record<string, string>;
};
export declare function generateFontCss(families: ResolvedFontFamily[], filePathMap: Map<string, string>, fallbackMap?: Map<string, {
    fallbackFamily: string;
    metrics: FallbackMetrics;
}>): string;
