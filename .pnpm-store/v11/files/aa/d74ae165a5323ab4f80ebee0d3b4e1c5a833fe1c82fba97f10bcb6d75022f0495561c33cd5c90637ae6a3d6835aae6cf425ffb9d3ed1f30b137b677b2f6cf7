const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const require_shared_useForwardExpose = require('../shared/useForwardExpose.cjs');
const require_Primitive_Primitive = require('../Primitive/Primitive.cjs');
const require_Drawer_DrawerProvider = require('./DrawerProvider.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));

//#region src/Drawer/DrawerIndentBackground.vue?vue&type=script&setup=true&lang.ts
var DrawerIndentBackground_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ (0, vue.defineComponent)({
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
		const { forwardRef } = require_shared_useForwardExpose.useForwardExpose();
		const providerContext = require_Drawer_DrawerProvider.injectDrawerProviderContext(null);
		return (_ctx, _cache) => {
			return (0, vue.openBlock)(), (0, vue.createBlock)((0, vue.unref)(require_Primitive_Primitive.Primitive), (0, vue.mergeProps)(props, {
				ref: (0, vue.unref)(forwardRef),
				"data-active": (0, vue.unref)(providerContext)?.active.value ? "" : void 0,
				"data-inactive": !(0, vue.unref)(providerContext)?.active.value ? "" : void 0
			}), {
				default: (0, vue.withCtx)(() => [(0, vue.renderSlot)(_ctx.$slots, "default")]),
				_: 3
			}, 16, ["data-active", "data-inactive"]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerIndentBackground.vue
var DrawerIndentBackground_default = DrawerIndentBackground_vue_vue_type_script_setup_true_lang_default;

//#endregion
Object.defineProperty(exports, 'DrawerIndentBackground_default', {
  enumerable: true,
  get: function () {
    return DrawerIndentBackground_default;
  }
});
//# sourceMappingURL=DrawerIndentBackground.cjs.map