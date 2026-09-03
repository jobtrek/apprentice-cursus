import { context } from "./context.js";
import { useForwardExpose } from "../shared/useForwardExpose.js";
import { Primitive } from "../Primitive/Primitive.js";
import { createBlock, defineComponent, mergeProps, onMounted, onUnmounted, openBlock, renderSlot, unref, withCtx } from "vue";

//#region src/DismissableLayer/DismissableLayerBranch.vue?vue&type=script&setup=true&lang.ts
var DismissableLayerBranch_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DismissableLayerBranch",
	props: {
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
		const { forwardRef, currentElement } = useForwardExpose();
		let registeredElement = null;
		onMounted(() => {
			registeredElement = currentElement.value;
			if (registeredElement) context.branches.add(registeredElement);
		});
		onUnmounted(() => {
			if (registeredElement) {
				context.branches.delete(registeredElement);
				registeredElement = null;
			}
		});
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), mergeProps({ ref: unref(forwardRef) }, props), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16);
		};
	}
});

//#endregion
//#region src/DismissableLayer/DismissableLayerBranch.vue
var DismissableLayerBranch_default = DismissableLayerBranch_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DismissableLayerBranch_default };
//# sourceMappingURL=DismissableLayerBranch.js.map