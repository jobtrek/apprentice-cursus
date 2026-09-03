import { computed, ref } from "vue";
import { useEventListener } from "@vueuse/core";

//#region src/Drawer/composables/useDrawerSnapPoints.ts
const SNAP_VELOCITY_THRESHOLD = .5;
const SNAP_VELOCITY_MULTIPLIER = 300;
const MAX_SNAP_VELOCITY = 4;
const FAST_SWIPE_VELOCITY = .5;
function parseSnapPoint(value, viewportHeight, rootFontSize) {
	let result = null;
	if (typeof value === "number") result = value >= 0 && value <= 1 ? value * viewportHeight : value;
	else if (value.endsWith("rem")) result = Number.parseFloat(value) * rootFontSize;
	else if (value.endsWith("px")) result = Number.parseFloat(value);
	if (result == null || !Number.isFinite(result) || result < 0) return null;
	return Math.round(result);
}
function useDrawerSnapPoints(options) {
	const { snapPoints, activeSnapPoint, popupHeight, viewportRef, onSnapPointChange } = options;
	const viewportHeight = ref(typeof window !== "undefined" ? window.innerHeight : 0);
	const rootFontSize = ref(typeof document !== "undefined" ? Number.parseFloat(getComputedStyle(document.documentElement).fontSize) || 16 : 16);
	if (typeof window !== "undefined") useEventListener(window, "resize", () => {
		viewportHeight.value = window.innerHeight;
	});
	const resolvedSnapPoints = computed(() => {
		const points = snapPoints.value;
		if (!points || points.length === 0) return [];
		const vh = viewportHeight.value;
		const fs = rootFontSize.value;
		const ph = popupHeight.value;
		const resolved = [];
		for (const pt of points) {
			const height = parseSnapPoint(pt, vh, fs);
			if (height == null) continue;
			if (resolved.some((r) => Math.abs(r.height - height) <= 1)) continue;
			const clampedHeight = Math.min(height, Math.min(ph, vh));
			resolved.push({
				value: pt,
				height: clampedHeight,
				offset: Math.max(0, ph - clampedHeight)
			});
		}
		return resolved.sort((a, b) => a.height - b.height);
	});
	/**
	* Resolve the currently-active snap point to its resolved entry, matching by
	* raw value OR by parsed-height equivalence. This lets a controlled drawer use
	* interchangeable representations (`'16rem'` vs `256`, `0.5` vs `400`) without
	* the release math falling back to offset `0` and jumping to the wrong target.
	*/
	function findActiveResolvedPoint() {
		if (!activeSnapPoint.value || resolvedSnapPoints.value.length === 0) return void 0;
		const activeHeight = parseSnapPoint(activeSnapPoint.value, viewportHeight.value, rootFontSize.value);
		return resolvedSnapPoints.value.find((r) => r.value === activeSnapPoint.value || activeHeight != null && Math.abs(r.height - activeHeight) <= 1);
	}
	const activeSnapPointOffset = computed(() => findActiveResolvedPoint()?.offset ?? null);
	/**
	* BaseUI-parity snap release math (ported from `DrawerViewport.tsx` lines ~577-714).
	*
	* Takes a **dismiss-signed** drag delta (positive = dismiss direction, negative =
	* open direction) and the raw velocity vector. Computes a velocity-boosted target
	* offset, then either dismisses, snaps to the closest point, or (in sequential
	* mode) advances one step based on physical crossing + fast-swipe velocity.
	*/
	function snapToNearest(dragDelta, velocity, direction, sequential) {
		const points = resolvedSnapPoints.value;
		if (points.length === 0) return;
		const ph = popupHeight.value;
		const axisVel = direction === "up" || direction === "down" ? velocity.y : velocity.x;
		const velSigned = direction === "up" || direction === "left" ? -axisVel : axisVel;
		const activePoint = findActiveResolvedPoint();
		const currentOffset = activePoint?.offset ?? 0;
		const dragTargetOffset = Math.max(0, Math.min(ph, currentOffset + dragDelta));
		let targetOffset = dragTargetOffset;
		if (Math.abs(velSigned) >= SNAP_VELOCITY_THRESHOLD) {
			const clampedVel = Math.max(-MAX_SNAP_VELOCITY, Math.min(MAX_SNAP_VELOCITY, velSigned));
			targetOffset = dragTargetOffset + clampedVel * SNAP_VELOCITY_MULTIPLIER;
		}
		let closest = points[0];
		let closestDist = Math.abs(targetOffset - closest.offset);
		for (const p of points) {
			const d = Math.abs(targetOffset - p.offset);
			if (d < closestDist) {
				closest = p;
				closestDist = d;
			}
		}
		const closeDistance = Math.abs(targetOffset - ph);
		if (closeDistance < closestDist) {
			onSnapPointChange(null);
			return;
		}
		if (sequential) {
			const sorted = [...points].sort((a, b) => a.offset - b.offset);
			const currentIdx = activePoint ? sorted.indexOf(activePoint) : -1;
			if (currentIdx < 0) {
				onSnapPointChange(closest.value);
				return;
			}
			const dragDir = Math.sign(dragDelta);
			const velDir = Math.sign(velSigned);
			const shouldAdvance = velDir === dragDir && Math.abs(velSigned) >= FAST_SWIPE_VELOCITY;
			const adjacentIdx = Math.max(0, Math.min(sorted.length - 1, currentIdx + dragDir));
			const adjacent = sorted[adjacentIdx];
			if (shouldAdvance) {
				onSnapPointChange(adjacent.value);
				return;
			}
			const crossed = dragDir > 0 ? targetOffset > adjacent.offset : targetOffset < adjacent.offset;
			onSnapPointChange(crossed ? adjacent.value : activeSnapPoint.value ?? closest.value);
			return;
		}
		onSnapPointChange(closest.value);
	}
	return {
		resolvedSnapPoints,
		activeSnapPointOffset,
		viewportHeight,
		snapToNearest
	};
}

//#endregion
export { useDrawerSnapPoints };
//# sourceMappingURL=useDrawerSnapPoints.js.map