import { useForwardExpose } from "../shared/useForwardExpose.js";
import { Primitive } from "../Primitive/Primitive.js";
import { DRAWER_CSS_VARS } from "./utils.js";
import { injectDrawerRootContext } from "./DrawerRoot.js";
import { useSwipeDismiss } from "../composables/useSwipeDismiss.js";
import { computed, createBlock, defineComponent, mergeProps, openBlock, renderSlot, unref, withCtx } from "vue";

//#region src/Drawer/DrawerSwipeArea.vue?vue&type=script&setup=true&lang.ts
var DrawerSwipeArea_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ defineComponent({
	__name: "DrawerSwipeArea",
	props: {
		swipeDirection: {
			type: String,
			required: false
		},
		disabled: {
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
			required: false,
			default: "div"
		}
	},
	setup(__props) {
		const props = __props;
		const { forwardRef, currentElement } = useForwardExpose();
		const rootContext = injectDrawerRootContext();
		const OPPOSITE = {
			up: "down",
			down: "up",
			left: "right",
			right: "left"
		};
		const openDirection = computed(() => {
			if (props.swipeDirection) return props.swipeDirection;
			return OPPOSITE[rootContext.swipeDirection.value] ?? "up";
		});
		const enabled = computed(() => !props.disabled && !rootContext.open.value);
		const directions = computed(() => [openDirection.value]);
		useSwipeDismiss({
			enabled,
			elementRef: currentElement,
			directions,
			movementCssVars: {
				x: DRAWER_CSS_VARS.swipeMovementX,
				y: DRAWER_CSS_VARS.swipeMovementY
			},
			onDismiss() {
				rootContext.onOpenChange(true, "swipe");
			},
			onSwipingChange(swiping) {
				rootContext.onNestedSwipingChange(swiping);
			}
		});
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), mergeProps(props, {
				ref: unref(forwardRef),
				"data-state": unref(rootContext).open.value ? "open" : "closed",
				"data-swipe-direction": openDirection.value
			}), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16, ["data-state", "data-swipe-direction"]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerSwipeArea.vue
var DrawerSwipeArea_default = DrawerSwipeArea_vue_vue_type_script_setup_true_lang_default;

//#endregion
export { DrawerSwipeArea_default };
//# sourceMappingURL=DrawerSwipeArea.js.map