const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const require_shared_useBodyScrollLock = require('../shared/useBodyScrollLock.cjs');
const require_shared_useForwardExpose = require('../shared/useForwardExpose.cjs');
const require_Primitive_Primitive = require('../Primitive/Primitive.cjs');
const require_Drawer_utils = require('./utils.cjs');
const require_Drawer_DrawerRoot = require('./DrawerRoot.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));

//#region src/Drawer/DrawerOverlayImpl.vue?vue&type=script&setup=true&lang.ts
var DrawerOverlayImpl_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ (0, vue.defineComponent)({
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
		const rootContext = require_Drawer_DrawerRoot.injectDrawerRootContext();
		const locked = require_shared_useBodyScrollLock.useBodyScrollLock(rootContext.open.value);
		(0, vue.watch)(() => rootContext.open.value, (open) => {
			locked.value = open;
		}, { immediate: true });
		require_shared_useForwardExpose.useForwardExpose();
		const dataAttributes = (0, vue.computed)(() => {
			const attrs = {
				"data-state": rootContext.open.value ? "open" : "closed",
				"data-swipe-direction": rootContext.swipeDirection.value
			};
			if (rootContext.isSwiping.value) attrs["data-swiping"] = "";
			return attrs;
		});
		return (_ctx, _cache) => {
			return (0, vue.openBlock)(), (0, vue.createBlock)((0, vue.unref)(require_Primitive_Primitive.Primitive), (0, vue.mergeProps)({
				as: _ctx.as,
				"as-child": _ctx.asChild
			}, dataAttributes.value, { style: {
				pointerEvents: "auto",
				userSelect: "none",
				[(0, vue.unref)(require_Drawer_utils.DRAWER_CSS_VARS).swipeProgress]: "0",
				[(0, vue.unref)(require_Drawer_utils.DRAWER_CSS_VARS).swipeStrength]: "1"
			} }), {
				default: (0, vue.withCtx)(() => [(0, vue.renderSlot)(_ctx.$slots, "default")]),
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
Object.defineProperty(exports, 'DrawerOverlayImpl_default', {
  enumerable: true,
  get: function () {
    return DrawerOverlayImpl_default;
  }
});
//# sourceMappingURL=DrawerOverlayImpl.cjs.map