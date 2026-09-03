import { useForwardExpose } from "../shared/useForwardExpose.js";
import { Primitive } from "../Primitive/Primitive.js";
import { injectDrawerRootContext } from "./DrawerRoot.js";
import { createBlock, defineComponent, mergeProps, openBlock, renderSlot, unref, withCtx } from "vue";

//#region src/Drawer/DrawerTitle.vue?vue&type=script&setup=true&lang.ts
var DrawerTitle_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DrawerTitle",
	props: {
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false,
			default: "h2"
		}
	},
	setup(__props) {
		const props = __props;
		const rootContext = injectDrawerRootContext();
		useForwardExpose();
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), mergeProps(props, { id: unref(rootContext).titleId }), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16, ["id"]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerTitle.vue
var DrawerTitle_default = DrawerTitle_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DrawerTitle_default };
//# sourceMappingURL=DrawerTitle.js.map