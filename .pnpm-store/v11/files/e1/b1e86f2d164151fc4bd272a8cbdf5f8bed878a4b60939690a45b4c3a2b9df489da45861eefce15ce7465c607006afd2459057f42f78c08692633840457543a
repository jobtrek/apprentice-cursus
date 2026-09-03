const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const require_shared_useForwardExpose = require('../shared/useForwardExpose.cjs');
const require_DismissableLayer_DismissableLayer = require('../DismissableLayer/DismissableLayer.cjs');
const require_FocusScope_FocusScope = require('../FocusScope/FocusScope.cjs');
const require_Drawer_utils = require('./utils.cjs');
const require_Drawer_DrawerRoot = require('./DrawerRoot.cjs');
const require_composables_useDrawerSnapPoints = require('../composables/useDrawerSnapPoints.cjs');
const require_composables_useSwipeDismiss = require('../composables/useSwipeDismiss.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));
const __vueuse_core = require_rolldown_runtime.__toESM(require("@vueuse/core"));

//#region src/Drawer/DrawerContentImpl.vue?vue&type=script&setup=true&lang.ts
var DrawerContentImpl_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ (0, vue.defineComponent)({
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
		const rootContext = require_Drawer_DrawerRoot.injectDrawerRootContext();
		const { forwardRef, currentElement } = require_shared_useForwardExpose.useForwardExpose();
		require_Drawer_utils.registerDrawerCssProperties();
		const { activeSnapPointOffset, snapToNearest } = require_composables_useDrawerSnapPoints.useDrawerSnapPoints({
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
				el.style.setProperty(require_Drawer_utils.DRAWER_CSS_VARS.snapPointOffset, `${signedOffset}px`);
			} else el.style.setProperty(require_Drawer_utils.DRAWER_CSS_VARS.snapPointOffset, "0px");
		}
		(0, vue.watch)(activeSnapPointOffset, writeSnapPointOffset);
		(0, __vueuse_core.useResizeObserver)(currentElement, ([entry]) => {
			if (!entry) return;
			if (rootContext.hasNestedDrawer.value && rootContext.popupHeight.value > 0) return;
			const h = entry.contentRect.height;
			rootContext.onPopupHeightChange(h);
			currentElement.value?.style.setProperty(require_Drawer_utils.DRAWER_CSS_VARS.height, `${h}px`);
		});
		(0, vue.watch)(() => rootContext.frontmostHeight.value, (h) => {
			currentElement.value?.style.setProperty(require_Drawer_utils.DRAWER_CSS_VARS.frontmostHeight, `${h}px`);
		});
		const hasSnapPoints = (0, vue.computed)(() => (rootContext.snapPoints.value?.length ?? 0) > 0);
		const swipeDirections = (0, vue.computed)(() => {
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
		const { isSwiping, dragOffset } = require_composables_useSwipeDismiss.useSwipeDismiss({
			enabled: (0, vue.computed)(() => rootContext.open.value),
			elementRef: currentElement,
			directions: swipeDirections,
			movementCssVars: {
				x: require_Drawer_utils.DRAWER_CSS_VARS.swipeMovementX,
				y: require_Drawer_utils.DRAWER_CSS_VARS.swipeMovementY
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
					const scalar = require_Drawer_utils.computeSwipeReleaseScalar({
						direction: dir,
						size,
						axisDelta,
						snapPointOffset: activeSnapPointOffset.value ?? 0,
						releaseVelocity
					});
					if (scalar != null) el.style.setProperty(require_Drawer_utils.DRAWER_CSS_VARS.swipeStrength, `${scalar}`);
					else el.style.setProperty(require_Drawer_utils.DRAWER_CSS_VARS.swipeStrength, "1");
				}
				if (hasSnapPoints.value) {
					const dir = rootContext.swipeDirection.value;
					const dragDelta = require_Drawer_utils.getDisplacement(dir, lastRawDelta.x, lastRawDelta.y);
					snapToNearest(dragDelta, velocity, dir, rootContext.snapToSequentialPoints.value);
					if (el) {
						writeSnapPointOffset();
						el.style.setProperty(require_Drawer_utils.DRAWER_CSS_VARS.swipeMovementX, "0px");
						el.style.setProperty(require_Drawer_utils.DRAWER_CSS_VARS.swipeMovementY, "0px");
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
		(0, vue.watch)(() => rootContext.open.value, (isOpen) => {
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
		const dataAttributes = (0, vue.computed)(() => {
			const attrs = {
				"data-state": rootContext.open.value ? "open" : "closed",
				"data-swipe-direction": rootContext.swipeDirection.value
			};
			if (isSwiping.value) attrs["data-swiping"] = "";
			if (rootContext.hasNestedDrawer.value) attrs["data-nested-drawer-open"] = "";
			return attrs;
		});
		(0, vue.watch)(() => rootContext.nestedOpenDrawerCount.value, (n) => {
			currentElement.value?.style.setProperty(require_Drawer_utils.DRAWER_CSS_VARS.nestedDrawers, `${n}`);
		});
		let unsubscribeNestedProgress;
		(0, vue.onMounted)(() => {
			rootContext.contentElement.value = currentElement.value;
			rootContext.notifyParentHasNestedDrawer?.(true);
			currentElement.value?.style.setProperty(require_Drawer_utils.DRAWER_CSS_VARS.nestedDrawers, `${rootContext.nestedOpenDrawerCount.value}`);
			writeSnapPointOffset();
			unsubscribeNestedProgress = rootContext.nestedSwipeProgressStore.subscribe(() => {
				const progress = rootContext.nestedSwipeProgressStore.getSnapshot();
				currentElement.value?.style.setProperty(require_Drawer_utils.DRAWER_CSS_VARS.swipeProgress, `${progress}`);
			});
		});
		(0, vue.onUnmounted)(() => {
			rootContext.notifyParentHasNestedDrawer?.(false);
			unsubscribeNestedProgress?.();
			clearOpenCompleteListener();
		});
		if (process.env.NODE_ENV !== "production") (0, vue.onMounted)(() => {
			if (!document.getElementById(rootContext.titleId)) console.warn(`Warning: \`DrawerContent\` requires a \`DrawerTitle\` for accessibility.`);
		});
		return (_ctx, _cache) => {
			return (0, vue.openBlock)(), (0, vue.createBlock)((0, vue.unref)(require_FocusScope_FocusScope.FocusScope_default), {
				"as-child": "",
				loop: "",
				trapped: props.trapFocus,
				onMountAutoFocus: _cache[0] || (_cache[0] = ($event) => emits("openAutoFocus", $event)),
				onUnmountAutoFocus: _cache[1] || (_cache[1] = ($event) => emits("closeAutoFocus", $event))
			}, {
				default: (0, vue.withCtx)(() => [(0, vue.createVNode)((0, vue.unref)(require_DismissableLayer_DismissableLayer.DismissableLayer_default), (0, vue.mergeProps)({
					id: (0, vue.unref)(rootContext).contentId,
					ref: (0, vue.unref)(forwardRef),
					as: _ctx.as,
					"as-child": _ctx.asChild,
					"disable-outside-pointer-events": _ctx.disableOutsidePointerEvents,
					role: "dialog",
					"aria-describedby": (0, vue.unref)(rootContext).descriptionId,
					"aria-labelledby": (0, vue.unref)(rootContext).titleId
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
					default: (0, vue.withCtx)(() => [(0, vue.renderSlot)(_ctx.$slots, "default")]),
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
Object.defineProperty(exports, 'DrawerContentImpl_default', {
  enumerable: true,
  get: function () {
    return DrawerContentImpl_default;
  }
});
//# sourceMappingURL=DrawerContentImpl.cjs.map