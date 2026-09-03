import { type ForegroundColorName } from "ansi-styles";
export declare const HEX_COLOR: RegExp;
export declare const DEFAULT_COLORS: string[];
type Rgb = [number, number, number];
/**
 * The names we accept, and the escape each one writes, both taken from
 * ansi-styles rather than restated here.
 *
 * This is the same list Ink resolves against: Ink colors through chalk, and
 * chalk builds its foreground methods from exactly these names. Hand-typing the
 * list would have left the two free to disagree, and a name we accept that chalk
 * does not know colors the stream label while silently leaving the sidebar plain.
 */
export declare const COLOR_NAMES: readonly ForegroundColorName[];
export declare const hexToRgb: (hex: string) => Rgb;
/**
 * The one gate for every color that reaches us, returning the single form the
 * rest of the code stores: hex lowercased, a name in the casing chalk expects.
 * Canonicalizing the case is not cosmetic — the old blanket `toLowerCase()`
 * would turn `redBright` into `redbright`, which chalk does not know, so Ink
 * would quietly drop the color while the stream label kept it.
 *
 * Returns undefined for anything invalid, so callers validate and normalize in
 * one step and cannot accidentally store a raw value.
 */
export declare function normalizeColor(value: string): string | undefined;
/**
 * The SGR sequence that turns the foreground on, for a normalized color.
 *
 * A name writes its own code so it picks up the theme's idea of the color; only
 * hex is resolved to truecolor, because a hex value has asked for one exact color.
 */
export declare function colorOpen(color: string): string;
/**
 * Black or white, whichever is legible on the given background. The sidebar
 * fills the selected tab with the command's color, which was safe to hard-code
 * black on only while every color came from a palette of light pastels.
 */
export declare function contrastText(background: string): string;
export {};
