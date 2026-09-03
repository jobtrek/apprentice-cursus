const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const require_shared_useForwardExpose = require('../shared/useForwardExpose.cjs');
const require_Primitive_Primitive = require('../Primitive/Primitive.cjs');
const require_Drawer_DrawerRoot = require('./DrawerRoot.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));

//#region src/Drawer/DrawerTrigger.vue?vue&type=script&setup=true&lang.ts
var DrawerTrigger_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ (0, vue.defineComponent)({
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
		const rootContext = require_Drawer_DrawerRoot.injectDrawerRootContext();
		const { forwardRef, currentElement } = require_shared_useForwardExpose.useForwardExpose();
		(0, vue.watch)(currentElement, (el) => {
			rootContext.triggerElement.value = el;
		}, { immediate: true });
		(0, vue.onUnmounted)(() => {
			if (rootContext.triggerElement.value === currentElement.value) rootContext.triggerElement.value = void 0;
		});
		return (_ctx, _cache) => {
			return (0, vue.openBlock)(), (0, vue.createBlock)((0, vue.unref)(require_Primitive_Primitive.Primitive), (0, vue.mergeProps)(props, {
				ref: (0, vue.unref)(forwardRef),
				type: _ctx.as === "button" ? "button" : void 0,
				"aria-haspopup": "dialog",
				"aria-expanded": (0, vue.unref)(rootContext).open.value,
				"aria-controls": (0, vue.unref)(rootContext).open.value ? (0, vue.unref)(rootContext).contentId : void 0,
				"data-state": (0, vue.unref)(rootContext).open.value ? "open" : "closed",
				onClick: (0, vue.unref)(rootContext).onOpenToggle
			}), {
				default: (0, vue.withCtx)(() => [(0, vue.renderSlot)(_ctx.$slots, "default")]),
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
Object.defineProperty(exports, 'DrawerTrigger_default', {
  enumerable: true,
  get: function () {
    return DrawerTrigger_default;
  }
});
//# sourceMappingURL=DrawerTrigger.cjs.map