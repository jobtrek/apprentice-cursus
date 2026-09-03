import { useForwardExpose } from "../shared/useForwardExpose.js";
import { Primitive } from "../Primitive/Primitive.js";
import { injectDrawerRootContext } from "./DrawerRoot.js";
import { createBlock, defineComponent, mergeProps, onUnmounted, openBlock, renderSlot, unref, watch, withCtx } from "vue";

//#region src/Drawer/DrawerTrigger.vue?vue&type=script&setup=true&lang.ts
var DrawerTrigger_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DrawerTrigger",
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
		const rootContext = injectDrawerRootContext();
		const { forwardRef, currentElement } = useForwardExpose();
		watch(currentElement, (el) => {
			rootContext.triggerElement.value = el;
		}, { immediate: true });
		onUnmounted(() => {
			if (rootContext.triggerElement.value === currentElement.value) rootContext.triggerElement.value = void 0;
		});
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), mergeProps(props, {
				ref: unref(forwardRef),
				type: _ctx.as === "button" ? "button" : void 0,
				"aria-haspopup": "dialog",
				"aria-expanded": unref(rootContext).open.value,
				"aria-controls": unref(rootContext).open.value ? unref(rootContext).contentId : void 0,
				"data-state": unref(rootContext).open.value ? "open" : "closed",
				onClick: unref(rootContext).onOpenToggle
			}), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16, [
				"type",
				"aria-expanded",
				"aria-controls",
				"data-state",
				"onClick"
			]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerTrigger.vue
var DrawerTrigger_default = DrawerTrigger_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DrawerTrigger_default };
//# sourceMappingURL=DrawerTrigger.js.map