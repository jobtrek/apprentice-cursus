import { useForwardExpose } from "../shared/useForwardExpose.js";
import { Primitive } from "../Primitive/Primitive.js";
import { injectDrawerRootContext } from "./DrawerRoot.js";
import { createBlock, defineComponent, mergeProps, openBlock, renderSlot, unref, withCtx } from "vue";

//#region src/Drawer/DrawerHandle.vue?vue&type=script&setup=true&lang.ts
var DrawerHandle_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DrawerHandle",
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
		useForwardExpose();
		const rootContext = injectDrawerRootContext();
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), mergeProps(props, {
				"aria-hidden": "true",
				"data-state": unref(rootContext).open.value ? "open" : "closed"
			}), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16, ["data-state"]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerHandle.vue
var DrawerHandle_default = DrawerHandle_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DrawerHandle_default };
//# sourceMappingURL=DrawerHandle.js.map