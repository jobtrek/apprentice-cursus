import { useForwardExpose } from "../shared/useForwardExpose.js";
import { Primitive } from "../Primitive/Primitive.js";
import { injectDrawerRootContext } from "./DrawerRoot.js";
import { createBlock, defineComponent, mergeProps, openBlock, renderSlot, unref, withCtx } from "vue";

//#region src/Drawer/DrawerDescription.vue?vue&type=script&setup=true&lang.ts
var DrawerDescription_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DrawerDescription",
	props: {
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false,
			default: "p"
		}
	},
	setup(__props) {
		const props = __props;
		useForwardExpose();
		const rootContext = injectDrawerRootContext();
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), mergeProps(props, { id: unref(rootContext).descriptionId }), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16, ["id"]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerDescription.vue
var DrawerDescription_default = DrawerDescription_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DrawerDescription_default };
//# sourceMappingURL=DrawerDescription.js.map