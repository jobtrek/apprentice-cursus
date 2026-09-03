import { type Supervisor } from "./supervisor.js";
import type { CommandDef, SupervisorRef } from "./types.js";
export type InlineOptions = {
    commandDefs: CommandDef[];
    cwd: string;
    timestamps: boolean;
    autoRestart: boolean;
    json: boolean;
    color: boolean;
    columns: number;
    supervisorRef?: SupervisorRef;
};
export type InlineRun = {
    supervisor: Supervisor;
    /** Resolves with the exit code once every command has stopped for good. */
    done: Promise<number>;
};
/**
 * Runs the commands without taking over the terminal: no alternate screen, no
 * Ink, no input handling — every line is written to stdout (or stderr) the
 * moment it arrives, and the run ends when the last command does.
 */
export declare function runInline({ commandDefs, cwd, timestamps, autoRestart, json, color, columns, supervisorRef, }: InlineOptions): InlineRun;
