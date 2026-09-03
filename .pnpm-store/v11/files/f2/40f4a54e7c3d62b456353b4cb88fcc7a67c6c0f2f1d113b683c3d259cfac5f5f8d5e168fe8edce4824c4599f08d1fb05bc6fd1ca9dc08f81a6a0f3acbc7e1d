import { useBodyScrollLock } from "../shared/useBodyScrollLock.js";
import { useForwardExpose } from "../shared/useForwardExpose.js";
import { Primitive } from "../Primitive/Primitive.js";
import { DRAWER_CSS_VARS } from "./utils.js";
import { injectDrawerRootContext } from "./DrawerRoot.js";
import { computed, createBlock, defineComponent, mergeProps, openBlock, renderSlot, unref, watch, withCtx } from "vue";

//#region src/Drawer/DrawerOverlayImpl.vue?vue&type=script&setup=true&lang.ts
var DrawerOverlayImpl_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DrawerOverlayImpl",
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
		const rootContext = injectDrawerRootContext();
		const locked = useBodyScrollLock(rootContext.open.value);
		watch(() => rootContext.open.value, (open) => {
			locked.value = open;
		}, { immediate: true });
		useForwardExpose();
		const dataAttributes = computed(() => {
			const attrs = {
				"data-state": rootContext.open.value ? "open" : "closed",
				"data-swipe-direction": rootContext.swipeDirection.value
			};
			if (rootContext.isSwiping.value) attrs["data-swiping"] = "";
			return attrs;
		});
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), mergeProps({
				as: _ctx.as,
				"as-child": _ctx.asChild
			}, dataAttributes.value, { style: {
				pointerEvents: "auto",
				userSelect: "none",
				[unref(DRAWER_CSS_VARS).swipeProgress]: "0",
				[unref(DRAWER_CSS_VARS).swipeStrength]: "1"
			} }), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16, [
				"as",
				"as-child",
				"style"
			]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerOverlayImpl.vue
var DrawerOverlayImpl_default = DrawerOverlayImpl_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DrawerOverlayImpl_default };
//# sourceMappingURL=DrawerOverlayImpl.js.map