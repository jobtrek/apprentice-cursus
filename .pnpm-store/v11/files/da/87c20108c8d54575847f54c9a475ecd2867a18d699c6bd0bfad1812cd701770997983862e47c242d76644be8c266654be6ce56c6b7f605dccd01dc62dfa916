import { useForwardExpose } from "../shared/useForwardExpose.js";
import { Presence_default } from "../Presence/Presence.js";
import { injectDrawerRootContext } from "./DrawerRoot.js";
import { DrawerOverlayImpl_default } from "./DrawerOverlayImpl.js";
import { createBlock, createCommentVNode, createVNode, defineComponent, mergeProps, openBlock, renderSlot, unref, withCtx } from "vue";

//#region src/Drawer/DrawerOverlay.vue?vue&type=script&setup=true&lang.ts
var DrawerOverlay_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DrawerOverlay",
	props: {
		forceMount: {
			type: Boolean,
			required: false,
			default: false
		},
		forceRender: {
			type: Boolean,
			required: false,
			default: false
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
		const props = __props;
		const rootContext = injectDrawerRootContext();
		const isNested = !!rootContext.notifyParentHasNestedDrawer;
		const { forwardRef } = useForwardExpose();
		return (_ctx, _cache) => {
			return unref(rootContext).modal.value && (!isNested || _ctx.forceRender) ? (openBlock(), createBlock(unref(Presence_default), {
				key: 0,
				present: _ctx.forceMount || unref(rootContext).open.value
			}, {
				default: withCtx(() => [createVNode(DrawerOverlayImpl_default, mergeProps(_ctx.$attrs, {
					ref: unref(forwardRef),
					as: _ctx.as,
					"as-child": _ctx.asChild
				}), {
					default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
					_: 3
				}, 16, ["as", "as-child"])]),
				_: 3
			}, 8, ["present"])) : createCommentVNode("v-if", true);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerOverlay.vue
var DrawerOverlay_default = DrawerOverlay_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DrawerOverlay_default };
//# sourceMappingURL=DrawerOverlay.js.map