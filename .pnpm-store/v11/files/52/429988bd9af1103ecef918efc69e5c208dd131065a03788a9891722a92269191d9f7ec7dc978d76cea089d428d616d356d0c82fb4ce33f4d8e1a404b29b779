import { spawn } from "node:child_process";
export const MAX_AUTO_RESTARTS = 5;
export const RESTART_DELAY_MS = 1000;
/**
 * How long a command has to have been up for a crash to be worth retrying.
 * Something that dies this fast never got off the ground — a typo, a port
 * already bound, a missing binary — and trying again five times just scrolls the
 * real error out of view while you fix it. Anything that ran for longer was
 * working until it wasn't, which is exactly the case auto-restart is for.
 */
export const MIN_UPTIME_FOR_RESTART_MS = 1000;
/**
 * How long to wait for a dead command's pipes to close before reporting the
 * exit anyway. Draining takes microseconds when the pipe is ours alone, so this
 * only ever fires for a command that left a grandchild holding its stdout — a
 * `&`'d process, a wrapper that forks — where the pipe never closes at all and
 * waiting for it would hang the run instead of ending it.
 */
export const EXIT_DRAIN_GRACE_MS = 500;
/**
 * How long a command gets to act on SIGTERM before it is killed outright.
 * Anything with cleanup to do — a dev server unlinking its hot file, a watcher
 * releasing a lock — does it in a handler that SIGKILL never reaches, so
 * shutting down without this leaves the working tree in a state the next
 * command has to be told to ignore. The wait ends the moment the last child is
 * gone, so this ceiling is only ever paid by something that ignores SIGTERM.
 */
export const TERMINATE_GRACE_MS = 2000;
/**
 * How long to wait for the SIGKILL that follows the grace period to land, so we
 * do not report a shutdown as finished with children still on their way out.
 */
export const FORCE_KILL_GRACE_MS = 250;
/**
 * Resolves when every promise has, or when the time is up — whichever comes
 * first. The timer is cleared on the fast path so a finished shutdown does not
 * hold the event loop open for the rest of the grace period.
 */
function settle(promises, ms) {
    return new Promise((resolve) => {
        const timer = setTimeout(resolve, ms);
        Promise.all(promises).then(() => {
            clearTimeout(timer);
            resolve();
        });
    });
}
export function createSupervisor({ commandDefs, cwd, columns, autoRestart, forceColor, minUptimeMs = MIN_UPTIME_FOR_RESTART_MS, restartDelayMs = RESTART_DELAY_MS, maxRestarts = MAX_AUTO_RESTARTS, handlers = {}, }) {
    const procs = [];
    const exitCodes = commandDefs.map(() => null);
    const partials = commandDefs.map(() => "");
    const running = commandDefs.map(() => false);
    const autoRestartCounts = commandDefs.map(() => 0);
    const spawnedAt = commandDefs.map(() => 0);
    const pendingRestarts = new Set();
    const restartTimers = new Map();
    // How many exits per command are ours rather than the process failing. A
    // plain flag was wrong in both directions: set with no exit coming, it
    // swallowed the next real crash, and it could not hold two rapid restarts.
    const intentionalKills = new Map();
    let stopped = false;
    let terminating = null;
    const supervisor = {
        handlers,
        procs,
        exitCodes,
        start,
        restart,
        killAll,
        terminate,
        stop,
    };
    function checkSettled(time) {
        if (stopped || running.some(Boolean) || pendingRestarts.size > 0) {
            return;
        }
        supervisor.handlers.onSettled?.({ time });
    }
    function spawnProcess(cmd, i) {
        const env = {
            ...process.env,
            COLUMNS: String(columns),
        };
        if (forceColor) {
            env.FORCE_COLOR = "1";
        }
        const proc = spawn("sh", ["-c", cmd.command], {
            cwd,
            detached: true,
            env,
            stdio: ["ignore", "pipe", "pipe"],
        });
        const spawnTime = new Date();
        running[i] = true;
        exitCodes[i] = null;
        spawnedAt[i] = spawnTime.getTime();
        supervisor.handlers.onSpawn?.({
            index: i,
            pid: proc.pid,
            time: spawnTime,
        });
        const handleData = (stream) => (data) => {
            const time = new Date();
            const chunk = data.toString().replace(/\r/g, "");
            supervisor.handlers.onData?.({ index: i, chunk, stream, time });
            partials[i] += chunk;
            const lines = partials[i].split("\n");
            partials[i] = lines.pop() ?? "";
            for (const text of lines) {
                supervisor.handlers.onLine?.({ index: i, text, stream, time });
            }
        };
        proc.stdout?.on("data", handleData("stdout"));
        proc.stderr?.on("data", handleData("stderr"));
        proc.on("error", (err) => {
            const time = new Date();
            supervisor.handlers.onSpawnError?.({
                index: i,
                message: err.message,
                time,
            });
            // 'error' is not guaranteed to be followed by 'exit', so settle the
            // command here or a failed spawn hangs inline mode forever.
            if (running[i]) {
                running[i] = false;
                supervisor.handlers.onFailed?.({
                    index: i,
                    code: null,
                    reason: "spawn-error",
                    time,
                });
                checkSettled(time);
            }
        });
        const handleExit = (code, signal) => {
            const ours = intentionalKills.get(i) ?? 0;
            if (ours > 0) {
                intentionalKills.set(i, ours - 1);
                return;
            }
            if (!running[i]) {
                return;
            }
            running[i] = false;
            exitCodes[i] = code;
            const time = new Date();
            if (partials[i].trim()) {
                supervisor.handlers.onLine?.({
                    index: i,
                    text: partials[i],
                    stream: "stdout",
                    time,
                });
            }
            partials[i] = "";
            supervisor.handlers.onExit?.({ index: i, code, signal, time });
            if (code === 0 || code === null) {
                autoRestartCounts[i] = 0;
                checkSettled(time);
                return;
            }
            autoRestartCounts[i]++;
            const uptime = time.getTime() - spawnedAt[i];
            const reason = !autoRestart
                ? "restart-disabled"
                : uptime < minUptimeMs
                    ? "crashed-immediately"
                    : autoRestartCounts[i] > maxRestarts
                        ? "attempts-exhausted"
                        : null;
            if (reason) {
                supervisor.handlers.onFailed?.({
                    index: i,
                    code,
                    reason,
                    time,
                });
                checkSettled(time);
                return;
            }
            supervisor.handlers.onRestartScheduled?.({
                index: i,
                attempt: autoRestartCounts[i],
                max: maxRestarts,
                time,
            });
            pendingRestarts.add(i);
            restartTimers.set(i, setTimeout(() => {
                restartTimers.delete(i);
                pendingRestarts.delete(i);
                restart(i, false);
            }, restartDelayMs));
        };
        // 'exit' means the process is gone, not that we have read what it
        // printed: whatever is still sitting in the pipe arrives afterwards.
        // Acting on it there loses the tail of every command that exits right
        // after writing, and settles the run before those lines are delivered.
        // So hold the exit until the pipes close, or until the grace period
        // says nobody is ever going to close them.
        let exited = null;
        let openPipes = 0;
        let drainTimer;
        let reported = false;
        const reportExit = () => {
            if (reported || !exited) {
                return;
            }
            reported = true;
            clearTimeout(drainTimer);
            handleExit(exited.code, exited.signal);
        };
        for (const pipe of [proc.stdout, proc.stderr]) {
            if (!pipe) {
                continue;
            }
            openPipes++;
            pipe.on("close", () => {
                openPipes--;
                if (openPipes === 0) {
                    reportExit();
                }
            });
        }
        proc.on("exit", (code, signal) => {
            exited = { code, signal };
            if (openPipes === 0) {
                reportExit();
                return;
            }
            drainTimer = setTimeout(reportExit, EXIT_DRAIN_GRACE_MS);
        });
        return proc;
    }
    function start() {
        commandDefs.forEach((cmd, i) => {
            procs[i] = spawnProcess(cmd, i);
        });
    }
    function restart(index, manual = true) {
        if (stopped) {
            return;
        }
        const timer = restartTimers.get(index);
        if (timer) {
            clearTimeout(timer);
            restartTimers.delete(index);
            pendingRestarts.delete(index);
        }
        if (manual) {
            autoRestartCounts[index] = 0;
            const proc = procs[index];
            // Only claim an exit when the kill is what causes it. Restarting a
            // process that already died — the whole point of `r` on a failed
            // tab — sends no signal and produces no exit event, so claiming one
            // here would swallow the replacement's crash and leave a dead
            // process looking healthy.
            if (proc?.pid &&
                proc.exitCode === null &&
                proc.signalCode === null) {
                try {
                    process.kill(-proc.pid, "SIGKILL");
                    intentionalKills.set(index, (intentionalKills.get(index) ?? 0) + 1);
                }
                catch {
                    //
                }
            }
        }
        running[index] = false;
        partials[index] = "";
        supervisor.handlers.onRestarted?.({ index, manual, time: new Date() });
        procs[index] = spawnProcess(commandDefs[index], index);
    }
    /**
     * Signals the whole process group rather than the child we spawned, because
     * the child is `sh` and the thing holding the port is its descendant. The
     * group outlives its leader while any member is still in it, so this is not
     * guarded on the leader being alive: a command that backgrounded something
     * has an exited `sh` and a group that still needs the signal.
     */
    function signalAll(signal, claimExits) {
        procs.forEach((proc, index) => {
            if (!proc?.pid) {
                return;
            }
            const live = proc.exitCode === null && proc.signalCode === null;
            try {
                process.kill(-proc.pid, signal);
                if (claimExits && live) {
                    intentionalKills.set(index, (intentionalKills.get(index) ?? 0) + 1);
                }
            }
            catch {
                //
            }
        });
    }
    function killAll() {
        signalAll("SIGKILL", false);
    }
    function clearRestartTimers() {
        for (const timer of restartTimers.values()) {
            clearTimeout(timer);
        }
        restartTimers.clear();
        pendingRestarts.clear();
    }
    async function runTerminate(graceMs) {
        // Synchronous down to the first await, so a caller that cannot wait
        // still gets the timers stopped and the signals sent before it returns.
        stopped = true;
        clearRestartTimers();
        const exits = procs
            .filter((proc) => proc?.pid &&
            proc.exitCode === null &&
            proc.signalCode === null)
            .map((proc) => new Promise((resolve) => {
            proc.once("exit", () => resolve());
        }));
        signalAll("SIGTERM", true);
        await settle(exits, graceMs);
        killAll();
        await settle(exits, FORCE_KILL_GRACE_MS);
    }
    function terminate(graceMs = TERMINATE_GRACE_MS) {
        terminating ??= runTerminate(graceMs);
        return terminating;
    }
    function stop() {
        // The unmount path has nothing to await on, so it starts the shutdown
        // and leaves it running; whoever owns the process awaits the same
        // promise through terminate().
        terminate().catch(() => { });
    }
    return supervisor;
}
