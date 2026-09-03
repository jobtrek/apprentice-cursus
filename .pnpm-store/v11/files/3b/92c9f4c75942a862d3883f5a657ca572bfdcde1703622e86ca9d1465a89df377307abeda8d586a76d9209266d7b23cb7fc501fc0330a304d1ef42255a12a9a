import { useEmitAsProps } from "../shared/useEmitAsProps.js";
import { useForwardExpose } from "../shared/useForwardExpose.js";
import { useHideOthers } from "../shared/useHideOthers.js";
import { injectDialogRootContext } from "./DialogRoot.js";
import { DialogContentImpl_default } from "./DialogContentImpl.js";
import { computed, createBlock, defineComponent, mergeProps, openBlock, renderSlot, unref, watch, withCtx } from "vue";

//#region src/Dialog/DialogContentModal.vue?vue&type=script&setup=true&lang.ts
var DialogContentModal_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DialogContentModal",
	props: {
		forceMount: {
			type: Boolean,
			required: false
		},
		trapFocus: {
			type: Boolean,
			required: false
		},
		disableOutsidePointerEvents: {
			type: Boolean,
			required: false,
			default: true
		},
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false
		},
		present: {
			type: Boolean,
			required: true
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
		const rootContext = injectDialogRootContext();
		const emitsAsProps = useEmitAsProps(emits);
		const { forwardRef, currentElement } = useForwardExpose();
		const ariaHiddenTarget = computed(() => props.present ? currentElement.value : void 0);
		useHideOthers(ariaHiddenTarget);
		const forwardedProps = computed(() => {
			const { present: _,...rest } = props;
			return rest;
		});
		watch(() => props.present, (isPresent, wasPresent) => {
			if (!isPresent && wasPresent) rootContext.triggerElement.value?.focus();
		});
		return (_ctx, _cache) => {
			return openBlock(), createBlock(DialogContentImpl_default, mergeProps({
				...forwardedProps.value,
				...unref(emitsAsProps)
			}, {
				ref: unref(forwardRef),
				present: _ctx.present,
				"trap-focus": unref(rootContext).open.value,
				"disable-outside-pointer-events": props.disableOutsidePointerEvents,
				onCloseAutoFocus: _cache[0] || (_cache[0] = (event) => {
					if (!event.defaultPrevented) {
						event.preventDefault();
						unref(rootContext).triggerElement.value?.focus();
					}
				}),
				onPointerDownOutside: _cache[1] || (_cache[1] = (event) => {
					const originalEvent = event.detail.originalEvent;
					const ctrlLeftClick = originalEvent.button === 0 && originalEvent.ctrlKey === true;
					const isRightClick = originalEvent.button === 2 || ctrlLeftClick;
					if (isRightClick) event.preventDefault();
				}),
				onFocusOutside: _cache[2] || (_cache[2] = (event) => {
					event.preventDefault();
				})
			}), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16, [
				"present",
				"trap-focus",
				"disable-outside-pointer-events"
			]);
		};
	}
});

//#endregion
//#region src/Dialog/DialogContentModal.vue
var DialogContentModal_default = DialogContentModal_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DialogContentModal_default };
//# sourceMappingURL=DialogContentModal.js.map