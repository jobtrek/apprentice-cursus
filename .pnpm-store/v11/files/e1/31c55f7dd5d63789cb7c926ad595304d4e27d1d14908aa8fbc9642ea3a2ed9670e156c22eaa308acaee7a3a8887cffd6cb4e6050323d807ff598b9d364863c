import { useEmitAsProps } from "../shared/useEmitAsProps.js";
import { useForwardExpose } from "../shared/useForwardExpose.js";
import { useHideOthers } from "../shared/useHideOthers.js";
import { Presence_default } from "../Presence/Presence.js";
import { injectDrawerRootContext } from "./DrawerRoot.js";
import { DrawerContentImpl_default } from "./DrawerContentImpl.js";
import { computed, createBlock, createCommentVNode, defineComponent, mergeProps, openBlock, ref, renderSlot, unref, withCtx } from "vue";

//#region src/Drawer/DrawerContent.vue?vue&type=script&setup=true&lang.ts
var DrawerContent_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DrawerContent",
	props: {
		forceMount: {
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
		const emitsAsProps = useEmitAsProps(emits);
		const { forwardRef, currentElement } = useForwardExpose();
		const hasInteractedOutside = ref(false);
		const hasPointerDownOutside = ref(false);
		const isFullModal = computed(() => rootContext.modal.value === true);
		const isTrapFocusOnly = computed(() => rootContext.modal.value === "trap-focus");
		const shouldTrapFocus = computed(() => (isFullModal.value || isTrapFocusOnly.value) && rootContext.open.value);
		const shouldHideOthers = computed(() => isFullModal.value ? currentElement.value : void 0);
		useHideOthers(shouldHideOthers);
		function onCloseAutoFocusNonModal(e) {
			if (!e.defaultPrevented) {
				if (!hasInteractedOutside.value) rootContext.triggerElement.value?.focus();
				e.preventDefault();
			}
			hasInteractedOutside.value = false;
			hasPointerDownOutside.value = false;
		}
		function onInteractOutsideNonModal(e) {
			if (!e.defaultPrevented) {
				hasInteractedOutside.value = true;
				if (e.detail.originalEvent.type === "pointerdown") hasPointerDownOutside.value = true;
			}
			const target = e.target;
			if (rootContext.triggerElement.value?.contains(target)) e.preventDefault();
			if (e.detail.originalEvent.type === "focusin" && hasPointerDownOutside.value) e.preventDefault();
		}
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Presence_default), { present: _ctx.forceMount || unref(rootContext).open.value }, {
				default: withCtx(() => [isFullModal.value ? (openBlock(), createBlock(DrawerContentImpl_default, mergeProps({
					key: 0,
					ref: unref(forwardRef)
				}, {
					...props,
					...unref(emitsAsProps),
					..._ctx.$attrs
				}, {
					"trap-focus": shouldTrapFocus.value,
					"disable-outside-pointer-events": true,
					onCloseAutoFocus: _cache[0] || (_cache[0] = (e) => {
						if (!e.defaultPrevented) {
							e.preventDefault();
							unref(rootContext).triggerElement.value?.focus();
						}
					}),
					onPointerDownOutside: _cache[1] || (_cache[1] = (e) => {
						const orig = e.detail.originalEvent;
						const isRightClick = orig.button === 2 || orig.button === 0 && orig.ctrlKey;
						if (isRightClick) e.preventDefault();
					}),
					onFocusOutside: _cache[2] || (_cache[2] = (e) => e.preventDefault())
				}), {
					default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
					_: 3
				}, 16, ["trap-focus"])) : (openBlock(), createBlock(DrawerContentImpl_default, mergeProps({
					key: 1,
					ref: unref(forwardRef)
				}, {
					...props,
					...unref(emitsAsProps),
					..._ctx.$attrs
				}, {
					"trap-focus": shouldTrapFocus.value,
					"disable-outside-pointer-events": false,
					onCloseAutoFocus: onCloseAutoFocusNonModal,
					onInteractOutside: onInteractOutsideNonModal
				}), {
					default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
					_: 3
				}, 16, ["trap-focus"]))]),
				_: 3
			}, 8, ["present"]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerContent.vue
var DrawerContent_default = DrawerContent_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DrawerContent_default };
//# sourceMappingURL=DrawerContent.js.map