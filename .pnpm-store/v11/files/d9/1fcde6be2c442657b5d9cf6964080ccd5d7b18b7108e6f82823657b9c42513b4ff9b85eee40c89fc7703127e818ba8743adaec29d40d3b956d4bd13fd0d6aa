import { type ChildProcess } from "node:child_process";
import type { CommandDef } from "./types.js";
export declare const MAX_AUTO_RESTARTS = 5;
export declare const RESTART_DELAY_MS = 1000;
/**
 * How long a command has to have been up for a crash to be worth retrying.
 * Something that dies this fast never got off the ground — a typo, a port
 * already bound, a missing binary — and trying again five times just scrolls the
 * real error out of view while you fix it. Anything that ran for longer was
 * working until it wasn't, which is exactly the case auto-restart is for.
 */
export declare const MIN_UPTIME_FOR_RESTART_MS = 1000;
/**
 * How long to wait for a dead command's pipes to close before reporting the
 * exit anyway. Draining takes microseconds when the pipe is ours alone, so this
 * only ever fires for a command that left a grandchild holding its stdout — a
 * `&`'d process, a wrapper that forks — where the pipe never closes at all and
 * waiting for it would hang the run instead of ending it.
 */
export declare const EXIT_DRAIN_GRACE_MS = 500;
/**
 * How long a command gets to act on SIGTERM before it is killed outright.
 * Anything with cleanup to do — a dev server unlinking its hot file, a watcher
 * releasing a lock — does it in a handler that SIGKILL never reaches, so
 * shutting down without this leaves the working tree in a state the next
 * command has to be told to ignore. The wait ends the moment the last child is
 * gone, so this ceiling is only ever paid by something that ignores SIGTERM.
 */
export declare const TERMINATE_GRACE_MS = 2000;
/**
 * How long to wait for the SIGKILL that follows the grace period to land, so we
 * do not report a shutdown as finished with children still on their way out.
 */
export declare const FORCE_KILL_GRACE_MS = 250;
/** Why a command is not going to be restarted. */
export type FailureReason = "spawn-error" | "crashed-immediately" | "attempts-exhausted" | "restart-disabled";
export type OutputStream = "stdout" | "stderr";
/**
 * Everything the supervisor knows how to report. It deliberately emits events
 * rather than formatted text: the TUI renders these as dim lines in a buffer,
 * inline mode prints them, and JSON mode serialises them. Baking the wording in
 * here would make the machine-readable output a scrape of the human one.
 */
export type SupervisorHandlers = {
    onSpawn?(e: {
        index: number;
        pid?: number;
        time: Date;
    }): void;
    /**
     * A raw chunk, carriage returns stripped, before it has been split into
     * lines. The tabbed view renders it so a partial line — a progress bar, a
     * prompt — shows up before its newline arrives; anything line-oriented
     * should use onLine instead.
     */
    onData?(e: {
        index: number;
        chunk: string;
        stream: OutputStream;
        time: Date;
    }): void;
    onLine?(e: {
        index: number;
        text: string;
        stream: OutputStream;
        time: Date;
    }): void;
    onSpawnError?(e: {
        index: number;
        message: string;
        time: Date;
    }): void;
    onExit?(e: {
        index: number;
        code: number | null;
        signal: NodeJS.Signals | null;
        time: Date;
    }): void;
    onRestartScheduled?(e: {
        index: number;
        attempt: number;
        max: number;
        time: Date;
    }): void;
    onRestarted?(e: {
        index: number;
        manual: boolean;
        time: Date;
    }): void;
    onFailed?(e: {
        index: number;
        code: number | null;
        reason: FailureReason;
        time: Date;
    }): void;
    /** Every command has stopped and none is waiting to be restarted. */
    onSettled?(e: {
        time: Date;
    }): void;
};
export type SupervisorOptions = {
    commandDefs: CommandDef[];
    cwd: string;
    /** Advertised to children as COLUMNS. They read it once, at spawn. */
    columns: number;
    autoRestart: boolean;
    forceColor: boolean;
    /** Overridable so tests can exercise the retry paths without real seconds. */
    minUptimeMs?: number;
    restartDelayMs?: number;
    maxRestarts?: number;
    handlers?: SupervisorHandlers;
};
export type Supervisor = {
    /** Mutable so a React caller can swap in fresh closures without respawning. */
    handlers: SupervisorHandlers;
    readonly procs: ChildProcess[];
    /** Last exit code seen per command; null if killed by a signal or still running. */
    readonly exitCodes: (number | null)[];
    start(): void;
    restart(index: number, manual?: boolean): void;
    killAll(): void;
    /**
     * Asks every command to stop, then kills whatever is left. Safe to call
     * more than once and from more than one place: the first call starts the
     * shutdown and every later one waits on that same shutdown rather than
     * starting a second.
     */
    terminate(graceMs?: number): Promise<void>;
    stop(): void;
};
export declare function createSupervisor({ commandDefs, cwd, columns, autoRestart, forceColor, minUptimeMs, restartDelayMs, maxRestarts, handlers, }: SupervisorOptions): Supervisor;
