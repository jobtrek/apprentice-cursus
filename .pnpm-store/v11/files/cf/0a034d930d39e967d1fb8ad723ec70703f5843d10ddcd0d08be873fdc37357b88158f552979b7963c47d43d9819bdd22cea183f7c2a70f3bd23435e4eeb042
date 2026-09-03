const require_rolldown_runtime = require('../rolldown-runtime.cjs');
const require_shared_useForwardExpose = require('../shared/useForwardExpose.cjs');
const require_Presence_Presence = require('../Presence/Presence.cjs');
const require_Drawer_DrawerRoot = require('./DrawerRoot.cjs');
const require_Drawer_DrawerOverlayImpl = require('./DrawerOverlayImpl.cjs');
const vue = require_rolldown_runtime.__toESM(require("vue"));

//#region src/Drawer/DrawerOverlay.vue?vue&type=script&setup=true&lang.ts
var DrawerOverlay_vue_vue_type_script_setup_true_lang_default = /* @__PURE__ */ (0, vue.defineComponent)({
	__name: "DrawerOverlay",
	props: {
		forceMount: {
			type: Boolean,
			required: false,
			default: false
		},
		forceRender: {
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
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		const rootContext = require_Drawer_DrawerRoot.injectDrawerRootContext();
		const isNested = !!rootContext.notifyParentHasNestedDrawer;
		const { forwardRef } = require_shared_useForwardExpose.useForwardExpose();
		return (_ctx, _cache) => {
			return (0, vue.unref)(rootContext).modal.value && (!isNested || _ctx.forceRender) ? ((0, vue.openBlock)(), (0, vue.createBlock)((0, vue.unref)(require_Presence_Presence.Presence_default), {
				key: 0,
				present: _ctx.forceMount || (0, vue.unref)(rootContext).open.value
			}, {
				default: (0, vue.withCtx)(() => [(0, vue.createVNode)(require_Drawer_DrawerOverlayImpl.DrawerOverlayImpl_default, (0, vue.mergeProps)(_ctx.$attrs, {
					ref: (0, vue.unref)(forwardRef),
					as: _ctx.as,
					"as-child": _ctx.asChild
				}), {
					default: (0, vue.withCtx)(() => [(0, vue.renderSlot)(_ctx.$slots, "default")]),
					_: 3
				}, 16, ["as", "as-child"])]),
				_: 3
			}, 8, ["present"])) : (0, vue.createCommentVNode)("v-if", true);
		};
	}
});

//#endregion
//#region src/Drawer/DrawerOverlay.vue
var DrawerOverlay_default = DrawerOverlay_vue_vue_type_script_setup_true_lang_default;

//#endregion
Object.defineProperty(exports, 'DrawerOverlay_default', {
  enumerable: true,
  get: function () {
    return DrawerOverlay_default;
  }
});
//# sourceMappingURL=DrawerOverlay.cjs.map