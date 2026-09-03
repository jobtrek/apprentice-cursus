import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { homedir } from "node:os";
import { Box, Text, useApp, useInput, useStdout } from "ink";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { contrastText } from "./color.js";
import { highlightLine, indexMatches } from "./search.js";
import { useProcesses } from "./use-processes.js";
import { useScroll } from "./use-scroll.js";
import { formatStreamContinuation, formatStreamLabel, MIN_TABS_LAYOUT_WIDTH, minTabsLayoutHeight, sidebarWidth, singleLine, tabbedTextWidth, wrapLine, } from "./util.js";
const SPINNER_FRAMES = "⠋⠙⠹⠸⠼⠴⠦⠧⠇⠏";
const SPAWN_SPINNER_MS = 2000;
const KeyBindings = ({ bindings }) => {
    return (_jsx(Text, { children: bindings.map(([k, desc], i) => (_jsxs(Text, { children: [i > 0 && _jsx(Text, { color: "#555555", children: " ".repeat(3) }), _jsx(Text, { color: "#888888", bold: true, children: k }), _jsxs(Text, { color: "#555555", children: [" ", desc] })] }, i))) }));
};
export function App({ commandDefs, cwd, initialStreamMode, bufferSize = 2_000, streamBufferSize = 10_000, timestamps = false, autoRestart = true, title, outputRef, supervisorRef: externalSupervisorRef, }) {
    const { stdout } = useStdout();
    const { exit } = useApp();
    const [rows, setRows] = useState(stdout?.rows ?? 24);
    const [cols, setCols] = useState(stdout?.columns ?? 80);
    const [selectedIndex, setSelectedIndex] = useState(0);
    const [streamMode, setStreamMode] = useState(initialStreamMode ||
        (stdout?.columns ?? 80) < MIN_TABS_LAYOUT_WIDTH ||
        (stdout?.rows ?? 24) < minTabsLayoutHeight(commandDefs.length));
    const [searchInputMode, setSearchInputMode] = useState(false);
    const [searchQuery, setSearchQuery] = useState("");
    const [currentMatch, setCurrentMatch] = useState(0);
    const [renderTick, setRenderTick] = useState(0);
    const [focus, setFocus] = useState("content");
    const [filterMode, setFilterMode] = useState(false);
    const [hiddenProcs, setHiddenProcs] = useState(new Set());
    const outputHeight = Math.max(0, streamMode ? rows - 4 : rows - 6);
    const { scrollOffset, hasNewOutput, totalLinesRef, notifyNewOutput, scrollDown, scrollUp, pageDown, pageUp, scrollToTop, scrollToBottom, resetScroll, } = useScroll(outputHeight);
    const matchCountRef = useRef(0);
    const pendingRender = useRef(false);
    const triggerRender = useCallback(() => {
        notifyNewOutput();
        if (!pendingRender.current) {
            pendingRender.current = true;
            setTimeout(() => {
                pendingRender.current = false;
                setRenderTick((t) => t + 1);
            }, 16);
        }
    }, [notifyNewOutput]);
    const { outputRowsRef, outputPendingRef, streamLinesRef, failedProcs, restartProcess, clearOutput, clearStream, spawnTimeRef, pendingRestartsRef, } = useProcesses({
        commandDefs,
        cwd,
        bufferSize,
        streamBufferSize,
        timestamps,
        autoRestart,
        title,
        columns: cols,
        triggerRender,
        outputRef,
        externalSupervisorRef,
    });
    const [animFrame, setAnimFrame] = useState(0);
    useEffect(() => {
        const handleResize = () => {
            setRows(stdout?.rows ?? 24);
            setCols(stdout?.columns ?? 80);
        };
        stdout?.on("resize", handleResize);
        return () => {
            stdout?.off("resize", handleResize);
        };
    }, [stdout]);
    useEffect(() => {
        const state = { active: false };
        const id = setInterval(() => {
            const now = Date.now();
            const anySpinning = spawnTimeRef.current.some((t) => t > 0 && now - t < SPAWN_SPINNER_MS);
            const isAnimating = anySpinning || pendingRestartsRef.current.size > 0;
            if (isAnimating) {
                state.active = true;
                setAnimFrame((f) => f + 1);
            }
            else if (state.active) {
                state.active = false;
                setAnimFrame((f) => f + 1);
            }
        }, 80);
        return () => clearInterval(id);
    }, []);
    const resolveFooter = () => {
        if (filterMode) {
            return (_jsxs(Box, { width: "100%", children: [_jsxs(Text, { color: "#e5c07b", bold: true, children: ["filter:", " "] }), commandDefs.map((cmd, i) => {
                        const hidden = hiddenProcs.has(i);
                        return (_jsxs(Text, { children: [i > 0 && _jsx(Text, { children: " ".repeat(3) }), _jsxs(Text, { color: hidden ? "#555555" : cmd.color, strikethrough: hidden, dimColor: hidden, bold: true, children: [i < 9 ? `${i + 1}` : " ", " "] }), _jsx(Text, { color: hidden ? "#555555" : cmd.color, strikethrough: hidden, dimColor: true, children: cmd.label })] }, i));
                    }), _jsx(Box, { flexGrow: 1 }), _jsx(KeyBindings, { bindings: [
                            ["1-9", "toggle"],
                            ["f", "done"],
                        ] })] }));
        }
        if (searchInputMode) {
            return (_jsxs(Text, { children: [_jsxs(Text, { color: "#e5c07b", bold: true, children: ["/", " "] }), _jsx(Text, { color: "#cccccc", children: searchQuery }), _jsx(Text, { color: "#e5c07b", children: "█" }), searchQuery && matchCount === 0 && (_jsx(Text, { color: "#e06c75", children: " no matches" })), searchQuery && matchCount > 0 && (_jsxs(Text, { color: "#555555", children: [" ", matchCount, " match", matchCount === 1 ? "" : "es"] }))] }));
        }
        if (searchQuery && matchCount > 0) {
            return (_jsxs(Text, { children: [_jsxs(Text, { color: "#e5c07b", bold: true, children: ["[", effectiveMatch + 1, "/", matchCount, "]"] }), _jsxs(Text, { color: "#888888", children: [" ", searchQuery] }), _jsx(Text, { color: "#555555", children: " " }), _jsx(KeyBindings, { bindings: [
                            ["n", "next"],
                            ["N", "prev"],
                            ["Esc", "clear"],
                        ] })] }));
        }
        const bindings = (() => {
            if (streamMode) {
                return [
                    ["↑/↓", "scroll"],
                    ["f", "filter"],
                    ["c", "clear"],
                    ["/", "search"],
                    ["t", "tabs"],
                    ["q", "quit"],
                ];
            }
            if (focus === "sidebar") {
                return [
                    ["↑/↓", "navigate"],
                    ["tab", "logs"],
                    ["r", "restart"],
                    ["c", "clear"],
                    ["/", "search"],
                    ["s", "stream"],
                    ["q", "quit"],
                ];
            }
            if (focus === "content") {
                return [
                    ["↑/↓", "scroll"],
                    ["tab", "tabs"],
                    ["c", "clear"],
                    ["/", "search"],
                    ["s", "stream"],
                    ["q", "quit"],
                ];
            }
            return [];
        })();
        return (_jsxs(Box, { width: "100%", children: [_jsx(KeyBindings, { bindings: bindings }), _jsx(Box, { flexGrow: 1 }), hiddenProcs.size > 0 && (_jsx(Text, { color: "#e5c07b", children: "\u25C6 filtered " })), hasNewOutput && _jsx(Text, { color: "#e5c07b", children: "\u2193 new output " })] }));
    };
    useInput((input, key) => {
        if (searchInputMode) {
            if (key.escape) {
                setSearchInputMode(false);
                setSearchQuery("");
                setCurrentMatch(0);
                return;
            }
            if (key.return) {
                setSearchInputMode(false);
                if (matchCountRef.current > 0) {
                    setCurrentMatch(0);
                }
                return;
            }
            if (key.backspace || key.delete) {
                setSearchQuery((q) => q.slice(0, -1));
                setCurrentMatch(0);
                return;
            }
            if (input && input.length === 1 && input.charCodeAt(0) >= 32) {
                setSearchQuery((q) => q + input);
                setCurrentMatch(0);
                return;
            }
            return;
        }
        if (filterMode) {
            if (key.escape || input === "f") {
                setFilterMode(false);
                return;
            }
            if (input && input >= "1" && input <= "9") {
                const idx = parseInt(input, 10) - 1;
                if (idx < commandDefs.length) {
                    setHiddenProcs((prev) => {
                        const next = new Set(prev);
                        if (next.has(idx)) {
                            next.delete(idx);
                        }
                        else {
                            if (next.size < commandDefs.length - 1) {
                                next.add(idx);
                            }
                        }
                        return next;
                    });
                    resetScroll();
                }
                return;
            }
            return;
        }
        if (input === "/") {
            setSearchInputMode(true);
            setSearchQuery("");
            setCurrentMatch(0);
            return;
        }
        if (searchQuery) {
            if (key.escape) {
                setSearchQuery("");
                setCurrentMatch(0);
                resetScroll();
                return;
            }
            if (input === "n") {
                const mc = matchCountRef.current;
                if (mc > 0) {
                    setCurrentMatch((m) => (m + 1) % mc);
                }
                return;
            }
            if (input === "N") {
                const mc = matchCountRef.current;
                if (mc > 0) {
                    setCurrentMatch((m) => (m - 1 + mc) % mc);
                }
                return;
            }
        }
        if (input === "q") {
            exit();
            return;
        }
        if (input === "c") {
            if (streamMode) {
                clearStream();
            }
            else {
                clearOutput(selectedIndex);
            }
            resetScroll();
            triggerRender();
            return;
        }
        if (input && input >= "1" && input <= "9") {
            const idx = parseInt(input, 10) - 1;
            if (idx < commandDefs.length) {
                setSelectedIndex(idx);
                setCurrentMatch(0);
                scrollToBottom();
            }
            return;
        }
        if (input === "s" && !streamMode) {
            setStreamMode(true);
            setCurrentMatch(0);
            resetScroll();
            return;
        }
        if (input === "t" && streamMode) {
            setStreamMode(false);
            setCurrentMatch(0);
            resetScroll();
            return;
        }
        if (streamMode && input === "f") {
            setFilterMode(true);
            return;
        }
        if (!streamMode) {
            if (input === "r") {
                restartProcess(selectedIndex);
                resetScroll();
                return;
            }
            if (key.tab) {
                setFocus((f) => (f === "sidebar" ? "content" : "sidebar"));
                return;
            }
            if (key.leftArrow) {
                setFocus("sidebar");
                return;
            }
            if (key.rightArrow) {
                setFocus("content");
                return;
            }
        }
        const effectiveFocus = streamMode ? "content" : focus;
        if (key.downArrow || input === "j") {
            if (effectiveFocus === "sidebar") {
                setSelectedIndex((i) => Math.min(i + 1, commandDefs.length - 1));
                setCurrentMatch(0);
                resetScroll();
            }
            else {
                scrollDown();
            }
            return;
        }
        if (key.upArrow || input === "k") {
            if (effectiveFocus === "sidebar") {
                setSelectedIndex((i) => Math.max(i - 1, 0));
                setCurrentMatch(0);
                resetScroll();
            }
            else {
                scrollUp();
            }
            return;
        }
        if (effectiveFocus === "content") {
            if (key.pageDown) {
                pageDown();
                return;
            }
            if (key.pageUp) {
                pageUp();
                return;
            }
            if (input === "g") {
                scrollToTop();
                return;
            }
            if (input === "G") {
                scrollToBottom();
                return;
            }
        }
    });
    const renderNow = Date.now();
    const maxLabelLen = Math.max(...commandDefs.map((c) => c.label.length));
    const computedSidebarWidth = sidebarWidth(commandDefs.map((c) => c.label), cols);
    const tabbedWrapWidth = tabbedTextWidth(commandDefs.map((c) => c.label), cols, timestamps);
    // renderTick stands in for the ref contents React cannot see change.
    const displayLines = useMemo(() => {
        if (!streamMode) {
            // Rows arrive pre-wrapped; only the unterminated tail is still raw.
            return outputRowsRef.current[selectedIndex].concat(wrapLine(outputPendingRef.current[selectedIndex], tabbedWrapWidth));
        }
        return streamLinesRef.current
            .filter((sl) => !hiddenProcs.has(sl.cmdIndex))
            .map((sl) => {
            const cmd = commandDefs[sl.cmdIndex];
            const prefix = sl.cont
                ? formatStreamContinuation(maxLabelLen, true)
                : formatStreamLabel(cmd.label, cmd.color, maxLabelLen, true);
            return `${sl.ts}${prefix}${sl.text}`;
        });
    }, [
        renderTick,
        streamMode,
        selectedIndex,
        hiddenProcs,
        commandDefs,
        maxLabelLen,
        tabbedWrapWidth,
        outputRowsRef,
        outputPendingRef,
        streamLinesRef,
    ]);
    const searchIndex = useMemo(() => indexMatches(displayLines, searchQuery), [displayLines, searchQuery]);
    const matchCount = searchIndex.count;
    matchCountRef.current = matchCount;
    totalLinesRef.current = displayLines.length;
    const effectiveMatch = matchCount > 0
        ? ((currentMatch % matchCount) + matchCount) % matchCount
        : 0;
    const windowStart = (() => {
        if (searchQuery) {
            if (searchInputMode || matchCount === 0) {
                return Math.max(0, displayLines.length - outputHeight);
            }
            const targetLine = searchIndex.lineOf[effectiveMatch] ?? 0;
            const halfWindow = Math.floor(outputHeight / 2);
            const maxStart = Math.max(0, displayLines.length - outputHeight);
            return Math.max(0, Math.min(targetLine - halfWindow, maxStart));
        }
        return scrollOffset ?? Math.max(0, displayLines.length - outputHeight);
    })();
    const visibleLines = displayLines
        .slice(windowStart, windowStart + outputHeight)
        .map((line, i) => searchQuery
        ? highlightLine(line, searchQuery, searchInputMode ? -1 : effectiveMatch, searchIndex.firstMatchOnLine.get(windowStart + i) ?? -1)
        : line);
    while (visibleLines.length < outputHeight) {
        visibleLines.push("");
    }
    const Footer = resolveFooter();
    const totalLines = displayLines.length;
    const showScrollbar = totalLines > outputHeight;
    let thumbStart = 0;
    let thumbEnd = 0;
    if (showScrollbar) {
        const currentOffset = windowStart;
        const maxOffset = Math.max(1, totalLines - outputHeight);
        const thumbSize = Math.max(1, Math.round((outputHeight / totalLines) * outputHeight));
        thumbStart = Math.round((currentOffset / maxOffset) * (outputHeight - thumbSize));
        thumbEnd = thumbStart + thumbSize;
    }
    const outputContent = visibleLines.map((line, i) => (_jsxs(Box, { height: 1, children: [_jsx(Box, { flexGrow: 1, children: _jsx(Text, { wrap: "truncate-end", children: line || " " }) }), showScrollbar && (_jsxs(Text, { color: i >= thumbStart && i < thumbEnd ? "#666666" : "#333333", children: [" ", i >= thumbStart && i < thumbEnd ? "┃" : "│"] }))] }, i)));
    if (streamMode) {
        return (_jsxs(Box, { flexDirection: "column", height: rows, width: cols, children: [_jsxs(Box, { height: 2, paddingX: 1, justifyContent: "space-between", children: [_jsx(Text, { color: "#ffffff", dimColor: true, children: title ?? "" }), _jsx(Text, { color: "#ffffff", dimColor: true, children: cwd.replace(homedir(), "~") })] }), _jsx(Box, { flexDirection: "column", flexGrow: 1, paddingLeft: 1, children: outputContent }), _jsx(Box, { height: 1 }), _jsx(Box, { height: 1, paddingLeft: 1, children: Footer })] }));
    }
    const focusedBorder = "#93c5fd";
    const unfocusedBorder = "#333333";
    return (_jsxs(Box, { flexDirection: "column", height: rows, width: cols, children: [_jsxs(Box, { height: 1, paddingX: 1, justifyContent: "space-between", children: [_jsx(Text, { color: "#ffffff", dimColor: true, children: title ?? "" }), _jsx(Text, { color: "#ffffff", dimColor: true, children: cwd.replace(homedir(), "~") })] }), _jsxs(Box, { flexDirection: "row", flexGrow: 1, children: [_jsx(Box, { flexDirection: "column", 
                        // Ink shrinks a fixed-width box like any other flex item, so
                        // a content pane asking for more room than it has takes a
                        // column off the sidebar while the tab rows are still padded
                        // to the full width, and every row wraps.
                        flexShrink: 0, width: computedSidebarWidth, borderStyle: "round", borderColor: focus === "sidebar" ? focusedBorder : unfocusedBorder, children: commandDefs.map((cmd, i) => {
                            const selected = i === selectedIndex;
                            const failed = failedProcs.has(i);
                            const isRestarting = pendingRestartsRef.current.has(i);
                            const isSpinning = renderNow - spawnTimeRef.current[i] <
                                SPAWN_SPINNER_MS;
                            const innerWidth = computedSidebarWidth - 2;
                            const spinnerChar = SPINNER_FRAMES[animFrame % SPINNER_FRAMES.length];
                            // The selected tab is filled with the command's own
                            // color, so everything drawn on it has to be legible
                            // against that fill rather than against the background.
                            const onFill = contrastText(cmd.color);
                            let indicator;
                            let indicatorColor;
                            let dim = false;
                            if (isRestarting) {
                                indicator = spinnerChar;
                                indicatorColor = selected ? onFill : "#e5c07b";
                            }
                            else if (failed) {
                                indicator = "✕";
                                indicatorColor = selected ? onFill : "#ef4444";
                            }
                            else if (isSpinning) {
                                indicator = spinnerChar;
                                indicatorColor = selected ? onFill : cmd.color;
                            }
                            else {
                                indicator = i < 9 ? `${i + 1}` : " ";
                                indicatorColor = selected ? onFill : "#555555";
                                dim = !selected;
                            }
                            const pad = Math.max(0, innerWidth - 3 - cmd.label.length);
                            const bg = selected ? cmd.color : undefined;
                            return (_jsxs(Box, { children: [_jsxs(Text, { backgroundColor: bg, color: indicatorColor, dimColor: dim, children: [" ", indicator, " "] }), _jsxs(Text, { backgroundColor: bg, color: selected
                                            ? onFill
                                            : failed
                                                ? "#ef4444"
                                                : cmd.color, children: [cmd.label, " ".repeat(pad)] })] }, i));
                        }) }), _jsxs(Box, { flexDirection: "column", flexGrow: 1, borderStyle: "round", borderColor: focus === "content" ? focusedBorder : unfocusedBorder, children: [_jsx(Box, { borderColor: focus === "content" ? "#666666" : unfocusedBorder, borderTop: false, borderLeft: false, borderRight: false, borderStyle: "single", paddingX: 1, children: _jsx(Text, { color: "#888888", wrap: "truncate-end", children: singleLine(commandDefs[selectedIndex].command) }) }), _jsx(Box, { paddingLeft: 1, flexDirection: "column", flexGrow: 1, children: outputContent })] })] }), _jsx(Box, { height: 1, paddingLeft: 1, children: Footer })] }));
}
