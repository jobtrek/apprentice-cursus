import { getDisplacement, getElementTransform } from "../Drawer/utils.js";
import { computed, onUnmounted, ref, toValue, watch } from "vue";
import { useEventListener } from "@vueuse/core";

//#region src/Drawer/composables/useSwipeDismiss.ts
const DEFAULT_SWIPE_THRESHOLD = 40;
const REVERSE_CANCEL_THRESHOLD = 10;
const MIN_DRAG_THRESHOLD = 1;
const AXIS_LOCK_SLOP = 6;
const AXIS_LOCK_BIAS = 2;
const MIN_RELEASE_VELOCITY_DURATION_MS = 16;
const MAX_RELEASE_VELOCITY_AGE_MS = 80;
const DEFAULT_IGNORE_SELECTOR = "button,a,input,select,textarea,label,[role=\"button\"]";
function findScrollableAncestor(el, axis) {
	if (!el || el === document.body) return null;
	const style = window.getComputedStyle(el);
	const overflow = axis === "vertical" ? style.overflowY : style.overflowX;
	if ((overflow === "auto" || overflow === "scroll") && (axis === "vertical" ? el.scrollHeight > el.clientHeight : el.scrollWidth > el.clientWidth)) return el;
	return findScrollableAncestor(el.parentElement, axis);
}
/**
* Returns whether the user can start a swipe dismiss from a scrollable element
* based on the scroll position and drag direction.
* Ported from BaseUI `useSwipeDismiss.ts:canSwipeFromScrollEdgeOnPendingMove`.
*
* Returns:
*   true  → swipe is allowed (scroll is at the relevant edge)
*   false → swipe is blocked (user is scrolling, not dismissing)
*   null  → the drag is not along a recognized axis; caller should fall through
*/
function canSwipeFromScrollEdge(scrollTarget, deltaX, deltaY, flags) {
	const absDx = Math.abs(deltaX);
	const absDy = Math.abs(deltaY);
	const useVerticalAxis = flags.hasVertical && deltaY !== 0 && (!flags.hasHorizontal || absDy >= absDx);
	if (useVerticalAxis) {
		const maxScrollTop = Math.max(0, scrollTarget.scrollHeight - scrollTarget.clientHeight);
		const atTop = scrollTarget.scrollTop <= 0;
		const atBottom = scrollTarget.scrollTop >= maxScrollTop;
		const movingDown = deltaY > 0;
		const movingUp = deltaY < 0;
		const canSwipeDown = movingDown && atTop && flags.allowDown;
		const canSwipeUp = movingUp && atBottom && flags.allowUp;
		return canSwipeDown || canSwipeUp;
	}
	const useHorizontalAxis = flags.hasHorizontal && deltaX !== 0 && (!flags.hasVertical || absDx > absDy);
	if (useHorizontalAxis) {
		const maxScrollLeft = Math.max(0, scrollTarget.scrollWidth - scrollTarget.clientWidth);
		const atLeft = scrollTarget.scrollLeft <= 0;
		const atRight = scrollTarget.scrollLeft >= maxScrollLeft;
		const movingRight = deltaX > 0;
		const movingLeft = deltaX < 0;
		const canSwipeRight = movingRight && atLeft && flags.allowRight;
		const canSwipeLeft = movingLeft && atRight && flags.allowLeft;
		return canSwipeRight || canSwipeLeft;
	}
	return null;
}
function useSwipeDismiss(options) {
	const { elementRef, directions, movementCssVars, swipeThreshold: swipeThresholdProp, canStart, onDismiss, onProgress, onCancel, onSwipeStart, onRelease, onSwipingChange } = options;
	const hasVertical = computed(() => toValue(directions).includes("up") || toValue(directions).includes("down"));
	const hasHorizontal = computed(() => toValue(directions).includes("left") || toValue(directions).includes("right"));
	const allowUp = computed(() => toValue(directions).includes("up"));
	const allowDown = computed(() => toValue(directions).includes("down"));
	const allowLeft = computed(() => toValue(directions).includes("left"));
	const allowRight = computed(() => toValue(directions).includes("right"));
	const drawerAxis = computed(() => {
		if (hasVertical.value === hasHorizontal.value) return null;
		return hasVertical.value ? "vertical" : "horizontal";
	});
	const isSwiping = ref(false);
	const swipeDirection = ref(void 0);
	const dragOffset = ref({
		x: 0,
		y: 0
	});
	let dragStartPos = {
		x: 0,
		y: 0
	};
	let intendedDirection;
	let maxDisplacement = 0;
	let cancelledSwipe = false;
	let isFirstMove = false;
	let pendingSwipe = false;
	let pendingSwipeStartPos = null;
	let swipeFromScrollable = false;
	let scrollableAncestor = null;
	let crossAxisScrollable = null;
	let drawerAxisAttributed = false;
	let preserveNativeCrossAxisScroll = false;
	let elementSize = {
		width: 0,
		height: 0
	};
	let swipeProgress = 0;
	let lastDragSample = null;
	let lastVelocity = {
		x: 0,
		y: 0
	};
	let lockedAxis = null;
	let activePointerId = null;
	let pointerStarted = false;
	function getThreshold(el, dir) {
		if (typeof swipeThresholdProp === "function") return Math.max(0, swipeThresholdProp({
			element: el,
			direction: dir
		}));
		return typeof swipeThresholdProp === "number" ? swipeThresholdProp : DEFAULT_SWIPE_THRESHOLD;
	}
	function setSwiping(next) {
		if (isSwiping.value === next) return;
		isSwiping.value = next;
		onSwipingChange?.(next);
	}
	function recordSample(offset, time) {
		if (lastDragSample && time > lastDragSample.time) {
			const dt = Math.max(time - lastDragSample.time, MIN_RELEASE_VELOCITY_DURATION_MS);
			lastVelocity = {
				x: (offset.x - lastDragSample.x) / dt,
				y: (offset.y - lastDragSample.y) / dt
			};
		}
		lastDragSample = {
			x: offset.x,
			y: offset.y,
			time
		};
	}
	function setCssVars(el, x, y) {
		el.style.setProperty(movementCssVars.x, `${x}px`);
		el.style.setProperty(movementCssVars.y, `${y}px`);
	}
	function clearCssVars(el) {
		el.style.setProperty(movementCssVars.x, "0px");
		el.style.setProperty(movementCssVars.y, "0px");
	}
	/**
	* Clears the per-gesture tracking state. Deliberately leaves the pointer
	* identity and the touch scroll-guard flags alone, so it is safe to call at
	* the start of a gesture as well as at the end of one.
	*/
	function resetGestureState() {
		setSwiping(false);
		swipeDirection.value = void 0;
		dragOffset.value = {
			x: 0,
			y: 0
		};
		dragStartPos = {
			x: 0,
			y: 0
		};
		intendedDirection = void 0;
		maxDisplacement = 0;
		cancelledSwipe = false;
		isFirstMove = false;
		pendingSwipe = false;
		pendingSwipeStartPos = null;
		elementSize = {
			width: 0,
			height: 0
		};
		swipeProgress = 0;
		lastDragSample = null;
		lastVelocity = {
			x: 0,
			y: 0
		};
		lockedAxis = null;
	}
	function reset() {
		resetGestureState();
		swipeFromScrollable = false;
		scrollableAncestor = null;
		crossAxisScrollable = null;
		drawerAxisAttributed = false;
		preserveNativeCrossAxisScroll = false;
		activePointerId = null;
		pointerStarted = false;
	}
	function startSwipe(el, pos) {
		resetGestureState();
		getElementTransform(el);
		dragStartPos = pos;
		pendingSwipeStartPos = pos;
		elementSize = {
			width: el.offsetWidth,
			height: el.offsetHeight
		};
		isFirstMove = true;
		pendingSwipe = true;
	}
	/**
	* Applies sqrt-damping to any axis moving in a disallowed direction.
	* Axes moving in an allowed direction pass through linearly.
	* Ported from BaseUI `useSwipeDismiss.ts:applyDirectionalDamping`.
	*/
	function applyDirectionalDamping(deltaX, deltaY) {
		const exponent = (value) => value >= 0 ? value ** .5 : -(Math.abs(value) ** .5);
		const dampAxis = (delta, allowNegative, allowPositive) => {
			if (!allowNegative && delta < 0) return exponent(delta);
			if (!allowPositive && delta > 0) return exponent(delta);
			return delta;
		};
		const newDx = hasHorizontal.value ? dampAxis(deltaX, allowLeft.value, allowRight.value) : exponent(deltaX);
		const newDy = hasVertical.value ? dampAxis(deltaY, allowUp.value, allowDown.value) : exponent(deltaY);
		return {
			x: newDx,
			y: newDy
		};
	}
	/**
	* Arbitrates a touchmove between the drawer swipe and a native scroll on the
	* cross axis. Returns `true` when the move must be left alone — the cross axis
	* already won the gesture, or neither axis has passed the slop yet.
	*/
	function shouldYieldTouchMove(e, pos) {
		if (preserveNativeCrossAxisScroll) return true;
		if (drawerAxisAttributed || !crossAxisScrollable || !drawerAxis.value) return false;
		if (!e.cancelable) {
			preserveNativeCrossAxisScroll = true;
			return true;
		}
		const isVerticalDrawerAxis = drawerAxis.value === "vertical";
		const drawerAxisDelta = isVerticalDrawerAxis ? pos.y - dragStartPos.y : pos.x - dragStartPos.x;
		const crossAxisDelta = isVerticalDrawerAxis ? pos.x - dragStartPos.x : pos.y - dragStartPos.y;
		const absDrawerAxisDelta = Math.abs(drawerAxisDelta);
		const absCrossAxisDelta = Math.abs(crossAxisDelta);
		if (absCrossAxisDelta >= AXIS_LOCK_SLOP && absCrossAxisDelta > absDrawerAxisDelta + AXIS_LOCK_BIAS) {
			preserveNativeCrossAxisScroll = true;
			return true;
		}
		if (absDrawerAxisDelta >= AXIS_LOCK_SLOP) {
			drawerAxisAttributed = true;
			return false;
		}
		return true;
	}
	function processMove(el, pos, time) {
		const rawDx = pos.x - dragStartPos.x;
		const rawDy = pos.y - dragStartPos.y;
		if (isFirstMove) {
			isFirstMove = false;
			const absX = Math.abs(rawDx);
			const absY = Math.abs(rawDy);
			if (hasVertical.value && hasHorizontal.value) lockedAxis = absX > absY ? "horizontal" : "vertical";
			else if (hasVertical.value) lockedAxis = "vertical";
			else lockedAxis = "horizontal";
		}
		const dx = lockedAxis === "vertical" ? 0 : rawDx;
		const dy = lockedAxis === "horizontal" ? 0 : rawDy;
		const dir = toValue(directions).find((d) => getDisplacement(d, dx, dy) > 0);
		if (pendingSwipe && pendingSwipeStartPos) {
			const pending = Math.max(Math.abs(dx), Math.abs(dy));
			if (pending < MIN_DRAG_THRESHOLD) return;
			pendingSwipe = false;
			setSwiping(true);
			onSwipeStart?.();
		}
		if (!isSwiping.value) return;
		if (!intendedDirection && dir) {
			intendedDirection = dir;
			swipeDirection.value = dir;
			maxDisplacement = getDisplacement(dir, dx, dy);
		}
		const currentDir = intendedDirection;
		if (currentDir && !cancelledSwipe) {
			const displacement = getDisplacement(currentDir, dx, dy);
			maxDisplacement = Math.max(maxDisplacement, displacement);
			if (maxDisplacement > DEFAULT_SWIPE_THRESHOLD / 2 && maxDisplacement - displacement > REVERSE_CANCEL_THRESHOLD) cancelledSwipe = true;
		}
		const damped = applyDirectionalDamping(dx, dy);
		dragOffset.value = {
			x: damped.x,
			y: damped.y
		};
		setCssVars(el, damped.x, damped.y);
		recordSample({
			x: damped.x,
			y: damped.y
		}, time);
		const currentEl = elementRef.value;
		const progressDir = currentDir ?? toValue(directions)[0];
		if (currentEl && progressDir) {
			const dim = progressDir === "up" || progressDir === "down" ? elementSize.height || currentEl.offsetHeight : elementSize.width || currentEl.offsetWidth;
			const threshold = getThreshold(currentEl, progressDir);
			const p = Math.min(1, Math.max(0, getDisplacement(progressDir, dx, dy) / (dim + threshold)));
			if (p !== swipeProgress) {
				swipeProgress = p;
				onProgress?.(p, {
					deltaX: damped.x,
					deltaY: damped.y,
					direction: currentDir
				});
			}
		}
	}
	function finishSwipe(el) {
		if (!isSwiping.value) {
			reset();
			return;
		}
		const displacement = getDisplacement(intendedDirection ?? toValue(directions)[0], dragOffset.value.x, dragOffset.value.y);
		const threshold = getThreshold(el, intendedDirection ?? toValue(directions)[0]);
		const now = performance.now();
		const velAge = lastDragSample ? now - lastDragSample.time : Infinity;
		const velocity = velAge > MAX_RELEASE_VELOCITY_AGE_MS ? {
			x: 0,
			y: 0
		} : lastVelocity;
		const releaseHandled = onRelease?.(velocity) === true;
		if (!releaseHandled) {
			const velInDirection = getDisplacement(intendedDirection ?? toValue(directions)[0], velocity.x, velocity.y);
			const shouldDismiss = !cancelledSwipe && (displacement >= threshold || velInDirection > .3);
			if (shouldDismiss) {
				el.setAttribute("data-swipe-dismissed", "");
				onDismiss?.();
			} else {
				clearCssVars(el);
				onCancel?.();
			}
		}
		reset();
	}
	function onPointerDown(e) {
		if (!toValue(options.enabled)) return;
		if (e.pointerType === "touch") return;
		if (e.button !== 0) return;
		if (canStart && !canStart()) return;
		const target = e.target;
		if (target?.closest(DEFAULT_IGNORE_SELECTOR)) return;
		const el = elementRef.value;
		if (!el) return;
		startSwipe(el, {
			x: e.clientX,
			y: e.clientY
		});
		activePointerId = e.pointerId;
		pointerStarted = true;
		try {
			el.setPointerCapture(e.pointerId);
		} catch {}
	}
	function onPointerMove(e) {
		if (!pointerStarted || e.pointerId !== activePointerId) return;
		const el = elementRef.value;
		if (!el) return;
		if ((e.buttons & 1) === 0) {
			finishSwipe(el);
			return;
		}
		processMove(el, {
			x: e.clientX,
			y: e.clientY
		}, e.timeStamp);
	}
	function onPointerUp(e) {
		if (!pointerStarted || e.pointerId !== activePointerId) return;
		const el = elementRef.value;
		if (!el) {
			reset();
			return;
		}
		finishSwipe(el);
	}
	/**
	* Ends an in-flight pointer drag that can no longer be tracked — pointer
	* capture was lost, or the window lost focus mid-drag (which is what happens
	* when the release lands over another application). The drawer settles from
	* wherever it was last dragged to.
	*/
	function onPointerInterrupted() {
		if (!pointerStarted) return;
		const el = elementRef.value;
		if (!el) {
			reset();
			return;
		}
		finishSwipe(el);
	}
	function onTouchStart(e) {
		if (!toValue(options.enabled)) return;
		if (canStart && !canStart()) return;
		const target = e.target;
		if (target?.closest(DEFAULT_IGNORE_SELECTOR)) return;
		const el = elementRef.value;
		if (!el) return;
		crossAxisScrollable = null;
		drawerAxisAttributed = false;
		preserveNativeCrossAxisScroll = false;
		if (!options.ignoreScrollableAncestors) {
			const axis = hasVertical.value ? "vertical" : "horizontal";
			const scrollable = findScrollableAncestor(target, axis);
			if (scrollable) {
				swipeFromScrollable = true;
				scrollableAncestor = scrollable;
			}
		}
		if (drawerAxis.value) {
			const crossAxis = drawerAxis.value === "vertical" ? "horizontal" : "vertical";
			crossAxisScrollable = findScrollableAncestor(target, crossAxis);
		}
		const t = e.touches[0];
		if (!t) return;
		startSwipe(el, {
			x: t.clientX,
			y: t.clientY
		});
	}
	function onTouchMove(e) {
		const el = elementRef.value;
		if (!el || !pendingSwipe && !isSwiping.value) return;
		const t = e.touches[0];
		if (!t) return;
		const pos = {
			x: t.clientX,
			y: t.clientY
		};
		if (shouldYieldTouchMove(e, pos)) return;
		if (swipeFromScrollable && pendingSwipe && scrollableAncestor) {
			const dx = pos.x - dragStartPos.x;
			const dy = pos.y - dragStartPos.y;
			const allowed = canSwipeFromScrollEdge(scrollableAncestor, dx, dy, {
				hasVertical: hasVertical.value,
				hasHorizontal: hasHorizontal.value,
				allowUp: allowUp.value,
				allowDown: allowDown.value,
				allowLeft: allowLeft.value,
				allowRight: allowRight.value
			});
			if (allowed === false) {
				reset();
				return;
			}
			if (allowed === true) {
				swipeFromScrollable = false;
				scrollableAncestor = null;
			}
		}
		const wasSwiping = isSwiping.value;
		processMove(el, pos, e.timeStamp);
		if (wasSwiping || isSwiping.value) e.preventDefault();
	}
	function onTouchEnd() {
		const el = elementRef.value;
		if (!el) return;
		finishSwipe(el);
	}
	const cleanups = [];
	watch(() => elementRef.value, (el) => {
		cleanups.forEach((fn) => fn());
		cleanups.length = 0;
		if (!el) return;
		const doc = el.ownerDocument;
		const win = doc.defaultView;
		cleanups.push(useEventListener(el, "pointerdown", onPointerDown), useEventListener(el, "pointermove", onPointerMove), useEventListener(el, "pointerup", onPointerUp), useEventListener(el, "pointercancel", onPointerUp), useEventListener(el, "lostpointercapture", onPointerInterrupted), useEventListener(doc, "pointerup", onPointerUp), useEventListener(doc, "pointercancel", onPointerUp), useEventListener(el, "touchstart", onTouchStart, { passive: true }), useEventListener(el, "touchmove", onTouchMove, { passive: false }), useEventListener(el, "touchend", onTouchEnd), useEventListener(el, "touchcancel", onTouchEnd));
		if (win) cleanups.push(useEventListener(win, "blur", onPointerInterrupted));
	}, { immediate: true });
	onUnmounted(() => {
		cleanups.forEach((fn) => fn());
		cleanups.length = 0;
		reset();
	});
	return {
		isSwiping,
		swipeDirection,
		dragOffset
	};
}

//#endregion
export { useSwipeDismiss };
//# sourceMappingURL=useSwipeDismiss.js.map