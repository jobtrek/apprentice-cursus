import { useForwardExpose } from "../shared/useForwardExpose.js";
import { DismissableLayer_default } from "../DismissableLayer/DismissableLayer.js";
import { FocusScope_default } from "../FocusScope/FocusScope.js";
import { DRAWER_CSS_VARS, computeSwipeReleaseScalar, getDisplacement, registerDrawerCssProperties } from "./utils.js";
import { injectDrawerRootContext } from "./DrawerRoot.js";
import { useDrawerSnapPoints } from "../composables/useDrawerSnapPoints.js";
import { useSwipeDismiss } from "../composables/useSwipeDismiss.js";
import { computed, createBlock, createVNode, defineComponent, mergeProps, onMounted, onUnmounted, openBlock, renderSlot, unref, watch, withCtx } from "vue";
import { useResizeObserver } from "@vueuse/core";

//#region src/Drawer/DrawerContentImpl.vue?vue&type=script&setup=true&lang.ts
var DrawerContentImpl_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DrawerContentImpl",
	props: {
		trapFocus: {
			type: Boolean,
			required: false
		},
		initialFocus: {
			type: [Boolean, null],
			required: false,
			skipCheck: true
		},
		finalFocus: {
			type: [Boolean, null],
			required: false,
			skipCheck: true
		},
		disableOutsidePointerEvents: {
			type: Boolean,
			required: false
		},
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false
		}
	},
	emits: [
		"escapeKeyDown",
		"pointerDownOutside",
		"focusOutside",
		"interactOutside",
		"openAutoFocus",
		"closeAutoFocus"
	],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const rootContext = injectDrawerRootContext();
		const { forwardRef, currentElement } = useForwardExpose();
		registerDrawerCssProperties();
		const { activeSnapPointOffset, snapToNearest } = useDrawerSnapPoints({
			snapPoints: rootContext.snapPoints,
			activeSnapPoint: rootContext.activeSnapPoint,
			popupHeight: rootContext.popupHeight,
			viewportRef: currentElement,
			onSnapPointChange: (point) => {
				if (point === null) rootContext.onOpenChange(false);
				else rootContext.setActiveSnapPoint(point);
			}
		});
		function writeSnapPointOffset() {
			const el = currentElement.value;
			if (!el) return;
			const offset = activeSnapPointOffset.value;
			if (offset != null) {
				const dir = rootContext.swipeDirection.value;
				const signedOffset = dir === "up" || dir === "left" ? -offset : offset;
				el.style.setProperty(DRAWER_CSS_VARS.snapPointOffset, `${signedOffset}px`);
			} else el.style.setProperty(DRAWER_CSS_VARS.snapPointOffset, "0px");
		}
		watch(activeSnapPointOffset, writeSnapPointOffset);
		useResizeObserver(currentElement, ([entry]) => {
			if (!entry) return;
			if (rootContext.hasNestedDrawer.value && rootContext.popupHeight.value > 0) return;
			const h$1 = entry.contentRect.height;
			rootContext.onPopupHeightChange(h$1);
			currentElement.value?.style.setProperty(DRAWER_CSS_VARS.height, `${h$1}px`);
		});
		watch(() => rootContext.frontmostHeight.value, (h$1) => {
			currentElement.value?.style.setProperty(DRAWER_CSS_VARS.frontmostHeight, `${h$1}px`);
		});
		const hasSnapPoints = computed(() => (rootContext.snapPoints.value?.length ?? 0) > 0);
		const swipeDirections = computed(() => {
			const dismiss = rootContext.swipeDirection.value;
			if (!hasSnapPoints.value) return [dismiss];
			const opposite = {
				up: "down",
				down: "up",
				left: "right",
				right: "left"
			};
			return [dismiss, opposite[dismiss]];
		});
		let lastRawDelta = {
			x: 0,
			y: 0
		};
		const { isSwiping, dragOffset } = useSwipeDismiss({
			enabled: computed(() => rootContext.open.value),
			elementRef: currentElement,
			directions: swipeDirections,
			movementCssVars: {
				x: DRAWER_CSS_VARS.swipeMovementX,
				y: DRAWER_CSS_VARS.swipeMovementY
			},
			canStart: () => !rootContext.nestedSwiping.value,
			onDismiss() {
				if (!hasSnapPoints.value) rootContext.onOpenChange(false, "swipe");
			},
			onRelease(velocity) {
				const el = currentElement.value;
				if (el) {
					const dir = rootContext.swipeDirection.value;
					const size = dir === "left" || dir === "right" ? el.offsetWidth : el.offsetHeight;
					const axisDelta = dir === "left" || dir === "right" ? lastRawDelta.x : lastRawDelta.y;
					const releaseVelocity = dir === "left" || dir === "right" ? velocity.x : velocity.y;
					const scalar = computeSwipeReleaseScalar({
						direction: dir,
						size,
						axisDelta,
						snapPointOffset: activeSnapPointOffset.value ?? 0,
						releaseVelocity
					});
					if (scalar != null) el.style.setProperty(DRAWER_CSS_VARS.swipeStrength, `${scalar}`);
					else el.style.setProperty(DRAWER_CSS_VARS.swipeStrength, "1");
				}
				if (hasSnapPoints.value) {
					const dir = rootContext.swipeDirection.value;
					const dragDelta = getDisplacement(dir, lastRawDelta.x, lastRawDelta.y);
					snapToNearest(dragDelta, velocity, dir, rootContext.snapToSequentialPoints.value);
					if (el) {
						writeSnapPointOffset();
						el.style.setProperty(DRAWER_CSS_VARS.swipeMovementX, "0px");
						el.style.setProperty(DRAWER_CSS_VARS.swipeMovementY, "0px");
					}
				}
				return hasSnapPoints.value;
			},
			onSwipingChange(swiping) {
				rootContext.onSwipingChange(swiping);
				rootContext.onNestedSwipingChange(swiping);
			},
			onProgress(progress, details) {
				if (details) lastRawDelta = {
					x: details.deltaX,
					y: details.deltaY
				};
				rootContext.onNestedSwipeProgressChange(progress);
			}
		});
		let pendingDismissReason;
		function onDismiss() {
			if (isSwiping.value) return;
			rootContext.onOpenChange(false, pendingDismissReason ?? "outside-press");
			pendingDismissReason = void 0;
		}
		function onEscapeKeyDown(event) {
			pendingDismissReason = "escape-key";
			emits("escapeKeyDown", event);
		}
		function onPointerDownOutside(event) {
			if (isSwiping.value) {
				event.preventDefault();
				return;
			}
			pendingDismissReason = "outside-press";
			emits("pointerDownOutside", event);
		}
		function onFocusOutside(event) {
			if (isSwiping.value) {
				event.preventDefault();
				return;
			}
			emits("focusOutside", event);
		}
		function onInteractOutside(event) {
			if (isSwiping.value) {
				event.preventDefault();
				return;
			}
			emits("interactOutside", event);
		}
		let openCompleteCleanup;
		function clearOpenCompleteListener() {
			openCompleteCleanup?.();
			openCompleteCleanup = void 0;
		}
		watch(() => rootContext.open.value, (isOpen) => {
			clearOpenCompleteListener();
			const el = currentElement.value;
			if (!el) {
				rootContext.notifyOpenComplete(isOpen);
				return;
			}
			const handler = (event) => {
				if (event.target !== el) return;
				clearOpenCompleteListener();
				rootContext.notifyOpenComplete(isOpen);
			};
			el.addEventListener("transitionend", handler);
			el.addEventListener("animationend", handler);
			openCompleteCleanup = () => {
				el.removeEventListener("transitionend", handler);
				el.removeEventListener("animationend", handler);
			};
		});
		const dataAttributes = computed(() => {
			const attrs = {
				"data-state": rootContext.open.value ? "open" : "closed",
				"data-swipe-direction": rootContext.swipeDirection.value
			};
			if (isSwiping.value) attrs["data-swiping"] = "";
			if (rootContext.hasNestedDrawer.value) attrs["data-nested-drawer-open"] = "";
			return attrs;
		});
		watch(() => rootContext.nestedOpenDrawerCount.value, (n) => {
			currentElement.value?.style.setProperty(DRAWER_CSS_VARS.nestedDrawers, `${n}`);
		});
		let unsubscribeNestedProgress;
		onMounted(() => {
			rootContext.contentElement.value = currentElement.value;
			rootContext.notifyParentHasNestedDrawer?.(true);
			currentElement.value?.style.setProperty(DRAWER_CSS_VARS.nestedDrawers, `${rootContext.nestedOpenDrawerCount.value}`);
			writeSnapPointOffset();
			unsubscribeNestedProgress = rootContext.nestedSwipeProgressStore.subscribe(() => {
				const progress = rootContext.nestedSwipeProgressStore.getSnapshot();
				currentElement.value?.style.setProperty(DRAWER_CSS_VARS.swipeProgress, `${progress}`);
			});
		});
		onUnmounted(() => {
			rootContext.notifyParentHasNestedDrawer?.(false);
			unsubscribeNestedProgress?.();
			clearOpenCompleteListener();
		});
		if (process.env.NODE_ENV !== "production") onMounted(() => {
			if (!document.getElementById(rootContext.titleId)) console.warn(`Warning: \`DrawerContent\` requires a \`DrawerTitle\` for accessibility.`);
		});
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(FocusScope_default), {
				"as-child": "",
				loop: "",
				trapped: props.trapFocus,
				onMountAutoFocus: _cache[0] || (_cache[0] = ($event) => emits("openAutoFocus", $event)),
				onUnmountAutoFocus: _cache[1] || (_cache[1] = ($event) => emits("closeAutoFocus", $event))
			}, {
				default: withCtx(() => [createVNode(unref(DismissableLayer_default), mergeProps({
					id: unref(rootContext).contentId,
					ref: unref(forwardRef),
					as: _ctx.as,
					"as-child": _ctx.asChild,
					"disable-outside-pointer-events": _ctx.disableOutsidePointerEvents,
					role: "dialog",
					"aria-describedby": unref(rootContext).descriptionId,
					"aria-labelledby": unref(rootContext).titleId
				}, {
					...dataAttributes.value,
					..._ctx.$attrs
				}, {
					onDismiss,
					onEscapeKeyDown,
					onFocusOutside,
					onInteractOutside,
					onPointerDownOutside
				}), {
					default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
					_: 3
				}, 16, [
					"id",
					"as",
					"as-child",
					"disable-outside-pointer-events",
					"aria-describedby",
					"aria-labelledby"
				])]),
				_: 3
			}, 8, ["trapped"]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerContentImpl.vue
var DrawerContentImpl_default = DrawerContentImpl_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DrawerContentImpl_default };
//# sourceMappingURL=DrawerContentImpl.js.map