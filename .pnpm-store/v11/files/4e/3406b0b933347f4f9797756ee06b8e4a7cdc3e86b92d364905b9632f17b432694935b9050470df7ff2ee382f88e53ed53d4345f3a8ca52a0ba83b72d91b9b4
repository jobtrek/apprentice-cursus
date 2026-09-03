const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const require_shared_useForwardExpose = require('../shared/useForwardExpose.cjs');
const require_Primitive_Primitive = require('../Primitive/Primitive.cjs');
const require_Drawer_DrawerRoot = require('./DrawerRoot.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));

//#region src/Drawer/DrawerViewport.vue?vue&type=script&setup=true&lang.ts
var DrawerViewport_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ (0, vue.defineComponent)({
	__name: "DrawerViewport",
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
		const rootContext = require_Drawer_DrawerRoot.injectDrawerRootContext();
		return (_ctx, _cache) => {
			return (0, vue.openBlock)(), (0, vue.createBlock)((0, vue.unref)(require_Primitive_Primitive.Primitive), (0, vue.mergeProps)(props, {
				ref: (0, vue.unref)(forwardRef),
				"data-drawer-viewport": "",
				"data-state": (0, vue.unref)(rootContext).open.value ? "open" : "closed"
			}), {
				default: (0, vue.withCtx)(() => [(0, vue.renderSlot)(_ctx.$slots, "default")]),
				_: 3
			}, 16, ["data-state"]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerViewport.vue
var DrawerViewport_default = DrawerViewport_vue_vue_type_script_setup_true_lang_default;

//#endregion
Object.defineProperty(exports, 'DrawerViewport_default', {
  enumerable: true,
  get: function () {
    return DrawerViewport_default;
  }
});
//# sourceMappingURL=DrawerViewport.cjs.map