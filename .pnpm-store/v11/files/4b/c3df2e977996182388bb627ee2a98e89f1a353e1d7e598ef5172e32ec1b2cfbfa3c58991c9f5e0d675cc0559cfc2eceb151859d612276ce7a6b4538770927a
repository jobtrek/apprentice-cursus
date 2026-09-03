import { useForwardExpose } from "../shared/useForwardExpose.js";
import { Primitive } from "../Primitive/Primitive.js";
import { injectDrawerProviderContext } from "./DrawerProvider.js";
import { createBlock, defineComponent, mergeProps, openBlock, renderSlot, unref, withCtx } from "vue";

//#region src/Drawer/DrawerIndentBackground.vue?vue&type=script&setup=true&lang.ts
var DrawerIndentBackground_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DrawerIndentBackground",
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
		const providerContext = injectDrawerProviderContext(null);
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), mergeProps(props, {
				ref: unref(forwardRef),
				"data-active": unref(providerContext)?.active.value ? "" : void 0,
				"data-inactive": !unref(providerContext)?.active.value ? "" : void 0
			}), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16, ["data-active", "data-inactive"]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerIndentBackground.vue
var DrawerIndentBackground_default = DrawerIndentBackground_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DrawerIndentBackground_default };
//# sourceMappingURL=DrawerIndentBackground.js.map