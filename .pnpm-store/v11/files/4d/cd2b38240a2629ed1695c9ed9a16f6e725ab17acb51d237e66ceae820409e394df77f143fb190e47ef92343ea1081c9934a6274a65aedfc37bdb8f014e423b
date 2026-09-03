const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const require_shared_useForwardExpose = require('../shared/useForwardExpose.cjs');
const require_Primitive_Primitive = require('../Primitive/Primitive.cjs');
const require_Drawer_DrawerProvider = require('./DrawerProvider.cjs');
const require_Drawer_utils = require('./utils.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));

//#region src/Drawer/DrawerIndent.vue?vue&type=script&setup=true&lang.ts
var DrawerIndent_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ (0, vue.defineComponent)({
	__name: "DrawerIndent",
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
		const { forwardRef, currentElement } = require_shared_useForwardExpose.useForwardExpose();
		const providerContext = require_Drawer_DrawerProvider.injectDrawerProviderContext(null);
		let unsubscribe;
		(0, vue.onMounted)(() => {
			const store = providerContext?.visualStateStore;
			if (!store) return;
			const el = currentElement.value;
			if (!el) return;
			const sync = () => {
				const { swipeProgress, frontmostHeight } = store.getSnapshot();
				el.style.setProperty(require_Drawer_utils.DRAWER_CSS_VARS.swipeProgress, swipeProgress > 0 ? `${swipeProgress}` : "0");
				if (frontmostHeight > 0) el.style.setProperty(require_Drawer_utils.DRAWER_CSS_VARS.height, `${frontmostHeight}px`);
				else el.style.removeProperty(require_Drawer_utils.DRAWER_CSS_VARS.height);
			};
			sync();
			unsubscribe = store.subscribe(sync);
		});
		(0, vue.onUnmounted)(() => {
			unsubscribe?.();
			const el = currentElement.value;
			if (el) {
				el.style.setProperty(require_Drawer_utils.DRAWER_CSS_VARS.swipeProgress, "0");
				el.style.removeProperty(require_Drawer_utils.DRAWER_CSS_VARS.height);
			}
		});
		return (_ctx, _cache) => {
			return (0, vue.openBlock)(), (0, vue.createBlock)((0, vue.unref)(require_Primitive_Primitive.Primitive), (0, vue.mergeProps)(props, {
				ref: (0, vue.unref)(forwardRef),
				"data-active": (0, vue.unref)(providerContext)?.active.value ? "" : void 0,
				"data-inactive": (0, vue.unref)(providerContext)?.active.value === false ? "" : void 0,
				style: { [(0, vue.unref)(require_Drawer_utils.DRAWER_CSS_VARS).swipeProgress]: "0" }
			}), {
				default: (0, vue.withCtx)(() => [(0, vue.renderSlot)(_ctx.$slots, "default")]),
				_: 3
			}, 16, [
				"data-active",
				"data-inactive",
				"style"
			]);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerIndent.vue
var DrawerIndent_default = DrawerIndent_vue_vue_type_script_setup_true_lang_default;

//#endregion
Object.defineProperty(exports, 'DrawerIndent_default', {
  enumerable: true,
  get: function () {
    return DrawerIndent_default;
  }
});
//# sourceMappingURL=DrawerIndent.cjs.map