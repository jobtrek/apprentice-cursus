export declare const systemMsg: (text: string) => string;
export declare const sanitizeTitle: (title: string) => string;
/**
 * A command on a single row. Ink breaks on a newline whatever the wrap mode, so
 * a command written across several lines would grow the tabbed header that shows
 * it — and the output pane's height is counted off a header one row tall.
 */
export declare const singleLine: (text: string) => string;
export declare const formatTimestamp: (time: Date) => string;
export declare function sidebarWidth(labels: string[], totalColumns: number): number;
export declare const MIN_TABS_LAYOUT_WIDTH = 80;
/**
 * Rows needed before the tabbed layout is worth using. The sidebar renders one
 * row per command and silently drops the overflow, so it has to fit every label
 * as well as leave the content pane something to show.
 */
export declare function minTabsLayoutHeight(commandCount: number): number;
export declare const TIMESTAMP_WIDTH = 9;
/**
 * The smallest terminal the TUI is worth rendering into, measured against the
 * stream layout because that is the one a small terminal falls back to. Rows:
 * its chrome plus enough lines to be reading output rather than a header.
 * Columns: its padding, scrollbar and label rule plus the same floor every
 * width calculation here clamps to.
 *
 * The label itself is left out of the column figure on purpose. This is about
 * the terminal being too small for any TUI, not about this particular set of
 * commands — long labels squeeze the pane, and `streamTextWidth` already
 * clamps for that, but they should not decide whether there is a TUI at all.
 */
export declare const MIN_ROWS: number;
export declare const MIN_COLUMNS: number;
export declare const fitsTui: (columns: number, rows: number) => boolean;
/** Columns left for output text in the tabbed content pane. */
export declare function tabbedTextWidth(labels: string[], totalColumns: number, timestamps: boolean): number;
/** Columns left for output text in the stream pane, past the label prefix. */
export declare function streamTextWidth(labels: string[], totalColumns: number, timestamps: boolean): number;
/**
 * The width to advertise to children as COLUMNS. They get it once at spawn and
 * it can never be updated, so it has to hold for both layouts — switching mode
 * with `s` or `t` must not invalidate it — so we take whichever mode is narrower.
 *
 * Erring narrow is the point. Output wider than the pane is re-wrapped by the
 * renderer, which costs a ragged right edge; output narrower than it just wraps
 * early. Tabbed is the narrower mode for any sane label, but that is emergent
 * from MAX_SIDEBAR_WIDTH rather than guaranteed: the sidebar stops widening at
 * 40 while the stream label prefix keeps growing, so past a ~39 character label
 * stream becomes the narrower one.
 */
export declare function childColumns(labels: string[], totalColumns: number, timestamps: boolean): number;
/**
 * Split one logical line into the screen rows it occupies. Callers treat the
 * result as independent buffer entries, which is what keeps the scroll, the
 * scrollbar and the search index working on a one-entry-per-row basis.
 *
 * stringWidth has its own ASCII fast path and costs a fraction of a microsecond
 * on the overwhelming majority of lines, so measuring first is far cheaper than
 * handing everything to wrapAnsi.
 */
export declare function wrapLine(text: string, width: number): string[];
/**
 * The label prefix in front of every interleaved line. The only implementation:
 * the stream pane, the flush on exit and inline mode all call it, so the same
 * run cannot come out looking like two different programs depending on where you
 * happened to read it. Its width is STREAM_LABEL_EXTRA past the longest label.
 */
export declare function formatStreamLabel(label: string, color: string, maxLabelLen: number, useColor: boolean): string;
/**
 * The same prefix for the second and later rows of a wrapped line: the rule
 * carries on so the block still reads as one command's output, but the label is
 * blank rather than repeated, which would look like separate lines.
 */
export declare function formatStreamContinuation(maxLabelLen: number, useColor: boolean): string;
/**
 * COLUMNS for inline mode. There is no pane to truncate against, so this only
 * stops children wrapping under their own label prefix.
 */
export declare function inlineChildColumns(labels: string[], totalColumns: number, timestamps: boolean): number;
