import type { CommandDef, CommandInput } from "./types.js";
/**
 * One CLI positional onto a CommandInput. The color attaches to the label rather
 * than sitting in a slot of its own, so only the first comma is structural:
 * everything after it is the command, whether it holds commas, colons or a word
 * that happens to name a color.
 */
export declare function parseCommandDef(value: string, previous: CommandInput[]): CommandInput[];
export declare function normalizeCommands(commands: CommandInput[]): CommandDef[];
export declare function parsePositiveInt(value: string): number;
