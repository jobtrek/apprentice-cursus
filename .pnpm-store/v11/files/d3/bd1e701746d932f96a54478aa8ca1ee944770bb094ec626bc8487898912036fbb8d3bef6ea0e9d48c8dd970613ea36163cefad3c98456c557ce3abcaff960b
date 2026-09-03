import type { MultiplexOptions } from "./types.js";
export { DEFAULT_COLORS } from "./color.js";
export type { CommandInput, MultiplexOptions } from "./types.js";
/**
 * Runs the commands and resolves with an exit code once everything has exited,
 * the terminal is restored and every child process is gone. Options are
 * validated before we touch the terminal, so a bad option throws with the screen
 * untouched.
 *
 * Renders the TUI when the terminal can support it and falls back to inline
 * output — every line printed as it arrives, no alternate screen, no input —
 * when it cannot, so a pipe, a CI job or a window too small to draw a layout in
 * gets usable output rather than an error.
 */
export declare function multiplex(options: MultiplexOptions): Promise<number>;
