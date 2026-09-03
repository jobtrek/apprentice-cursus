import { useForwardExpose } from "../shared/useForwardExpose.js";
import { Primitive } from "../Primitive/Primitive.js";
import { injectDrawerRootContext } from "./DrawerRoot.js";
import { createBlock, defineComponent, mergeProps, openBlock, renderSlot, unref, withCtx } from "vue";

//#region src/Drawer/DrawerViewport.vue?vue&type=script&setup=true&lang.ts
var DrawerViewport_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DrawerViewport",
	props: {
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false,
			default: "div"
		}
	},
	setup(__props) {
		const props = __props;
		const { forwardRef } = useForwardExpose();
		const rootContext = injectDrawerRootContext();
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), mergeProps(props, {
				ref: unref(forwardRef),
				"data-drawer-viewport": "",
				"data-state": unref(rootContext).open.value ? "open" : "closed"
			}), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16, ["data-state"]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerViewport.vue
var DrawerViewport_default = DrawerViewport_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DrawerViewport_default };
//# sourceMappingURL=DrawerViewport.js.map