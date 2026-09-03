import { useForwardExpose } from "../shared/useForwardExpose.js";
import { Presence_default } from "../Presence/Presence.js";
import { injectDialogRootContext } from "./DialogRoot.js";
import { DialogOverlayImpl_default } from "./DialogOverlayImpl.js";
import { createBlock, createCommentVNode, createVNode, defineComponent, mergeProps, openBlock, renderSlot, unref, vShow, withCtx, withDirectives } from "vue";

//#region src/Dialog/DialogOverlay.vue?vue&type=script&setup=true&lang.ts
var DialogOverlay_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DialogOverlay",
	props: {
		forceMount: {
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
	setup(__props) {
		const rootContext = injectDialogRootContext();
		const { forwardRef } = useForwardExpose();
		return (_ctx, _cache) => {
			return unref(rootContext)?.modal.value ? (openBlock(), createBlock(unref(Presence_default), {
				key: 0,
				present: _ctx.forceMount || unref(rootContext).open.value,
				"force-mount": _ctx.forceMount || !unref(rootContext).unmountOnHide.value
			}, {
				default: withCtx(({ present }) => [withDirectives(createVNode(DialogOverlayImpl_default, mergeProps(_ctx.$attrs, {
					ref: unref(forwardRef),
					as: _ctx.as,
					"as-child": _ctx.asChild,
					present: unref(rootContext).unmountOnHide.value || present
				}), {
					default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
					_: 2
				}, 1040, [
					"as",
					"as-child",
					"present"
				]), [[vShow, unref(rootContext).unmountOnHide.value || present]])]),
				_: 3
			}, 8, ["present", "force-mount"])) : createCommentVNode("v-if", true);
		};
	}
});

//#endregion
//#region src/Dialog/DialogOverlay.vue
var DialogOverlay_default = DialogOverlay_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DialogOverlay_default };
//# sourceMappingURL=DialogOverlay.js.map