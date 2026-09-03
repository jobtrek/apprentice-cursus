const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const require_shared_useForwardExpose = require('../shared/useForwardExpose.cjs');
const require_Primitive_Primitive = require('../Primitive/Primitive.cjs');
const require_Drawer_utils = require('./utils.cjs');
const require_Drawer_DrawerRoot = require('./DrawerRoot.cjs');
const require_composables_useSwipeDismiss = require('../composables/useSwipeDismiss.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));

//#region src/Drawer/DrawerSwipeArea.vue?vue&type=script&setup=true&lang.ts
var DrawerSwipeArea_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ (0, vue.defineComponent)({
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
		const { forwardRef, currentElement } = require_shared_useForwardExpose.useForwardExpose();
		const rootContext = require_Drawer_DrawerRoot.injectDrawerRootContext();
		const OPPOSITE = {
			up: "down",
			down: "up",
			left: "right",
			right: "left"
		};
		const openDirection = (0, vue.computed)(() => {
			if (props.swipeDirection) return props.swipeDirection;
			return OPPOSITE[rootContext.swipeDirection.value] ?? "up";
		});
		const enabled = (0, vue.computed)(() => !props.disabled && !rootContext.open.value);
		const directions = (0, vue.computed)(() => [openDirection.value]);
		require_composables_useSwipeDismiss.useSwipeDismiss({
			enabled,
			elementRef: currentElement,
			directions,
			movementCssVars: {
				x: require_Drawer_utils.DRAWER_CSS_VARS.swipeMovementX,
				y: require_Drawer_utils.DRAWER_CSS_VARS.swipeMovementY
			},
			onDismiss() {
				rootContext.onOpenChange(true, "swipe");
			},
			onSwipingChange(swiping) {
				rootContext.onNestedSwipingChange(swiping);
			}
		});
		return (_ctx, _cache) => {
			return (0, vue.openBlock)(), (0, vue.createBlock)((0, vue.unref)(require_Primitive_Primitive.Primitive), (0, vue.mergeProps)(props, {
				ref: (0, vue.unref)(forwardRef),
				"data-state": (0, vue.unref)(rootContext).open.value ? "open" : "closed",
				"data-swipe-direction": openDirection.value
			}), {
				default: (0, vue.withCtx)(() => [(0, vue.renderSlot)(_ctx.$slots, "default")]),
				_: 3
			}, 16, ["data-state", "data-swipe-direction"]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerSwipeArea.vue
var DrawerSwipeArea_default = DrawerSwipeArea_vue_vue_type_script_setup_true_lang_default;

//#endregion
Object.defineProperty(exports, 'DrawerSwipeArea_default', {
  enumerable: true,
  get: function () {
    return DrawerSwipeArea_default;
  }
});
//# sourceMappingURL=DrawerSwipeArea.cjs.map