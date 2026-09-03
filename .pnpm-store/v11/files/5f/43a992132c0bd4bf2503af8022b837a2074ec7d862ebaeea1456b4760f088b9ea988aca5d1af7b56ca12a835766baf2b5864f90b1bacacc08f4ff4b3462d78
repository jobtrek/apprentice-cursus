import type { BaseFontOptions, FontDefinition, FontFormat, FontFormatKeyword, FontProviderType, FontStyle, FontWeight, FormatConfig, LocalVariantDefinition, ResolvedFontFamily, ResolvedFontVariant } from './types.js';
export declare const FORMATS: FormatConfig[];
export declare function formatKeyword(format: FontFormat): FontFormatKeyword;
export declare function inferWeightFromFilename(filePath: string): FontWeight;
export declare function inferStyleFromFilename(filePath: string): FontStyle;
export declare function inferLocalVariantFromFilename(filePath: string): {
    weight: FontWeight;
    style: FontStyle;
};
export declare function looksLikeVariableFontFilename(filePath: string): boolean;
export declare function resolveLocalShorthandVariants(definition: FontDefinition, localConfig: {
    src: string;
}, projectRoot: string): Promise<ResolvedFontVariant[]>;
export declare function familyToVariable(family: string): string;
export declare function familyToSlug(family: string): string;
export declare function aliasToVariable(alias: string): string;
export declare function buildFontDefinition(family: string, provider: FontProviderType, options?: BaseFontOptions, extra?: Partial<FontDefinition>): FontDefinition;
export declare function buildResolvedFamily(definition: FontDefinition, variants: ResolvedFontVariant[]): ResolvedFontFamily;
export declare function inferFormat(filePath: string): FontFormat;
export declare function validateFontDefinition(definition: FontDefinition): void;
export declare function mergeFontDefinitions(fonts: FontDefinition[]): FontDefinition[];
export declare function validateFontsConfig(fonts: FontDefinition[]): FontDefinition[];
export declare function resolveLocalExplicitVariants(definition: FontDefinition, localConfig: {
    variants: LocalVariantDefinition[];
}, projectRoot: string): ResolvedFontVariant[];
export declare function resolveLocalVariants(definition: FontDefinition, projectRoot: string): Promise<ResolvedFontVariant[]>;
export declare function resolveLocalFont(definition: FontDefinition, projectRoot: string): Promise<ResolvedFontFamily>;
