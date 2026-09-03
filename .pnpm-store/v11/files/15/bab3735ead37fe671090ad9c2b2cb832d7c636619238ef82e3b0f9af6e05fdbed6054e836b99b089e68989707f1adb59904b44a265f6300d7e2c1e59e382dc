import { useEmitAsProps } from "../shared/useEmitAsProps.js";
import { useForwardExpose } from "../shared/useForwardExpose.js";
import { Presence_default } from "../Presence/Presence.js";
import { injectDialogRootContext } from "./DialogRoot.js";
import { DialogContentModal_default } from "./DialogContentModal.js";
import { DialogContentNonModal_default } from "./DialogContentNonModal.js";
import { createBlock, createCommentVNode, defineComponent, mergeProps, openBlock, renderSlot, unref, vShow, withCtx, withDirectives } from "vue";

//#region src/Dialog/DialogContent.vue?vue&type=script&setup=true&lang.ts
var DialogContent_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DialogContent",
	props: {
		forceMount: {
			type: Boolean,
			required: false
		},
		disableOutsidePointerEvents: {
			type: Boolean,
			required: false,
			default: void 0
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
		const rootContext = injectDialogRootContext();
		const emitsAsProps = useEmitAsProps(emits);
		const { forwardRef } = useForwardExpose();
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Presence_default), {
				present: _ctx.forceMount || unref(rootContext).open.value,
				"force-mount": _ctx.forceMount || !unref(rootContext).unmountOnHide.value
			}, {
				default: withCtx(({ present }) => [unref(rootContext).modal.value ? withDirectives((openBlock(), createBlock(DialogContentModal_default, mergeProps({
					key: 0,
					ref: unref(forwardRef),
					present: unref(rootContext).unmountOnHide.value || present
				}, {
					...props,
					...unref(emitsAsProps),
					..._ctx.$attrs
				}), {
					default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
					_: 2
				}, 1040, ["present"])), [[vShow, unref(rootContext).unmountOnHide.value || present]]) : withDirectives((openBlock(), createBlock(DialogContentNonModal_default, mergeProps({
					key: 1,
					ref: unref(forwardRef),
					present: unref(rootContext).unmountOnHide.value || present
				}, {
					...props,
					...unref(emitsAsProps),
					..._ctx.$attrs
				}), {
					default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
					_: 2
				}, 1040, ["present"])), [[vShow, unref(rootContext).unmountOnHide.value || present]])]),
				_: 3
			}, 8, ["present", "force-mount"]);
		};
	}
});

//#endregion
//#region src/Dialog/DialogContent.vue
var DialogContent_default = DialogContent_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DialogContent_default };
//# sourceMappingURL=DialogContent.js.map