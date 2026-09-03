import { useForwardExpose } from "../shared/useForwardExpose.js";
import { Primitive } from "../Primitive/Primitive.js";
import { injectDrawerRootContext } from "./DrawerRoot.js";
import { createBlock, defineComponent, mergeProps, openBlock, renderSlot, unref, withCtx } from "vue";

//#region src/Drawer/DrawerClose.vue?vue&type=script&setup=true&lang.ts
var DrawerClose_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DrawerClose",
	props: {
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false,
			default: "button"
		}
	},
	setup(__props) {
		const props = __props;
		useForwardExpose();
		const rootContext = injectDrawerRootContext();
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), mergeProps(props, {
				type: _ctx.as === "button" ? "button" : void 0,
				onClick: _cache[0] || (_cache[0] = ($event) => unref(rootContext).onOpenChange(false, "close-press"))
			}), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16, ["type"]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerClose.vue
var DrawerClose_default = DrawerClose_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DrawerClose_default };
//# sourceMappingURL=DrawerClose.js.map